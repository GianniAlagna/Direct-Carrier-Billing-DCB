//
//  ActivationViewController.m
//  QTPass - iPhone OS 2.x
//
//  Built in code (no NIB) so the whole UI is readable in one file.
//  Networking: asynchronous NSURLConnection with delegate callbacks.
//  Response format: XML plist (parsed by NSPropertyListSerialization).
//

#import "ActivationViewController.h"
#import "QTPassAppDelegate.h"
#import "QTKeychain.h"
#import "QTHotp.h"
#import <CommonCrypto/CommonDigest.h>

@interface ActivationViewController (Private)
- (UITextField *)fieldWithFrame:(CGRect)frame placeholder:(NSString *)text secure:(BOOL)secure;
- (void)post:(NSString *)path params:(NSDictionary *)params;
- (void)updateForStep;
- (void)finishWithPin:(NSString *)pin;
@end

@implementation ActivationViewController

@synthesize connection, responseData, activationResult;

- (void)loadView
{
    UIView *v = [[UIView alloc] initWithFrame:[[UIScreen mainScreen] applicationFrame]];
    v.backgroundColor = [UIColor blackColor];
    self.view = v;
    [v release];
    self.title = @"QT Pass";

    infoLabel = [[UILabel alloc] initWithFrame:CGRectMake(20, 20, 280, 60)];
    infoLabel.numberOfLines = 3;
    infoLabel.backgroundColor = [UIColor clearColor];
    infoLabel.textColor = [UIColor whiteColor];
    infoLabel.font = [UIFont systemFontOfSize:15];
    [self.view addSubview:infoLabel];

    phoneField = [self fieldWithFrame:CGRectMake(20, 90, 280, 40) placeholder:@"079 123 45 67" secure:NO];
    phoneField.keyboardType = UIKeyboardTypePhonePad;
    codeField = [self fieldWithFrame:CGRectMake(20, 90, 280, 40) placeholder:@"SMS code (6 digits)" secure:NO];
    codeField.keyboardType = UIKeyboardTypeNumberPad;
    pinField = [self fieldWithFrame:CGRectMake(20, 90, 280, 40) placeholder:@"Choose 4-digit app PIN" secure:YES];
    pinField.keyboardType = UIKeyboardTypeNumberPad;

    actionButton = [[UIButton buttonWithType:UIButtonTypeRoundedRect] retain];
    actionButton.frame = CGRectMake(20, 145, 280, 44);
    [actionButton addTarget:self action:@selector(action:) forControlEvents:UIControlEventTouchUpInside];
    [self.view addSubview:actionButton];

    spinner = [[UIActivityIndicatorView alloc] initWithActivityIndicatorStyle:UIActivityIndicatorViewStyleWhite];
    spinner.center = CGPointMake(160, 220);
    spinner.hidesWhenStopped = YES;
    [self.view addSubview:spinner];

    step = 0;
    [self updateForStep];
}

- (UITextField *)fieldWithFrame:(CGRect)frame placeholder:(NSString *)text secure:(BOOL)secure
{
    UITextField *f = [[UITextField alloc] initWithFrame:frame];
    f.borderStyle = UITextBorderStyleRoundedRect;
    f.font = [UIFont systemFontOfSize:22];
    f.placeholder = text;
    f.secureTextEntry = secure;
    f.delegate = self;
    f.hidden = YES;
    [self.view addSubview:f];
    return f; // retained by us (ivar), released in dealloc
}

- (void)updateForStep
{
    phoneField.hidden = (step != 0);
    codeField.hidden  = (step != 1);
    pinField.hidden   = (step != 2);

    if (step == 0) {
        infoLabel.text = @"Enter the mobile number you registered with QT. We send you an activation code by SMS.";
        [actionButton setTitle:@"Send SMS code" forState:UIControlStateNormal];
        [phoneField becomeFirstResponder];
    } else if (step == 1) {
        infoLabel.text = @"Enter the 6-digit code from the SMS.";
        [actionButton setTitle:@"Activate" forState:UIControlStateNormal];
        [codeField becomeFirstResponder];
    } else {
        infoLabel.text = @"Choose an app PIN. You need it every time you show your pass.";
        [actionButton setTitle:@"Save PIN" forState:UIControlStateNormal];
        [pinField becomeFirstResponder];
    }
}

- (void)action:(id)sender
{
    if (step == 0) {
        [self post:@"/activate.php" params:[NSDictionary dictionaryWithObject:phoneField.text forKey:@"msisdn"]];
    } else if (step == 1) {
        NSString *device = [[UIDevice currentDevice] name];
        [self post:@"/activate_confirm.php" params:[NSDictionary dictionaryWithObjectsAndKeys:
            phoneField.text, @"msisdn", codeField.text, @"code", device, @"device", nil]];
    } else if ([pinField.text length] == 4) {
        [self finishWithPin:pinField.text];
    }
}

#pragma mark Networking (NSURLConnection, 2008)

- (void)post:(NSString *)path params:(NSDictionary *)params
{
    NSMutableArray *pairs = [NSMutableArray array];
    for (NSString *key in params) {
        NSString *value = (NSString *)CFURLCreateStringByAddingPercentEscapes(NULL,
            (CFStringRef)[params objectForKey:key], NULL, CFSTR("+&=?/ "), kCFStringEncodingUTF8);
        [pairs addObject:[NSString stringWithFormat:@"%@=%@", key, value]];
        [value release];
    }
    NSData *body = [[pairs componentsJoinedByString:@"&"] dataUsingEncoding:NSUTF8StringEncoding];

    NSURL *url = [NSURL URLWithString:[QT_API_BASE stringByAppendingString:path]];
    NSMutableURLRequest *req = [NSMutableURLRequest requestWithURL:url
                                                       cachePolicy:NSURLRequestReloadIgnoringLocalCacheData
                                                   timeoutInterval:30.0];
    [req setHTTPMethod:@"POST"];
    [req setValue:@"application/x-www-form-urlencoded" forHTTPHeaderField:@"Content-Type"];
    [req setHTTPBody:body];

    self.responseData = [NSMutableData data];
    NSURLConnection *c = [[NSURLConnection alloc] initWithRequest:req delegate:self];
    self.connection = c;
    [c release];

    actionButton.enabled = NO;
    [spinner startAnimating];
    [UIApplication sharedApplication].networkActivityIndicatorVisible = YES;
}

- (void)connection:(NSURLConnection *)c didReceiveData:(NSData *)data
{
    [responseData appendData:data];
}

- (void)connection:(NSURLConnection *)c didFailWithError:(NSError *)error
{
    [spinner stopAnimating];
    actionButton.enabled = YES;
    [UIApplication sharedApplication].networkActivityIndicatorVisible = NO;
    UIAlertView *a = [[UIAlertView alloc] initWithTitle:@"No connection"
        message:@"Activation needs network. Please try outside the club." delegate:nil
        cancelButtonTitle:@"OK" otherButtonTitles:nil];
    [a show];
    [a release];
}

- (void)connectionDidFinishLoading:(NSURLConnection *)c
{
    [spinner stopAnimating];
    actionButton.enabled = YES;
    [UIApplication sharedApplication].networkActivityIndicatorVisible = NO;

    NSString *err = nil;
    NSDictionary *plist = [NSPropertyListSerialization propertyListFromData:responseData
        mutabilityOption:NSPropertyListImmutable format:NULL errorDescription:&err];
    if (err != nil) {
        [err release]; // 2008 API quirk: errorDescription string is owned by the caller
    }

    if (![plist isKindOfClass:[NSDictionary class]]) {
        infoLabel.text = @"Server error. Please try again later.";
        return;
    }
    NSString *status = [plist objectForKey:@"status"];

    if (step == 0 && [status isEqualToString:@"SENT"]) {
        step = 1;
    } else if (step == 1 && [status isEqualToString:@"OK"]) {
        self.activationResult = plist;
        step = 2;
    } else {
        infoLabel.text = [plist objectForKey:@"message"];
        return;
    }
    [self updateForStep];
}

#pragma mark Finish

- (void)finishWithPin:(NSString *)pin
{
    NSData *secret = [QTHotp dataFromBase32String:[activationResult objectForKey:@"secret"]];
    if (secret == nil || [secret length] != 20) {
        infoLabel.text = @"Activation data invalid. Please start again.";
        step = 0;
        [self updateForStep];
        return;
    }

    // PIN is stored only as SHA-256(member_no + pin) in the Keychain
    NSString *salted = [NSString stringWithFormat:@"%@:%@", [activationResult objectForKey:@"member_no"], pin];
    NSData *raw = [salted dataUsingEncoding:NSUTF8StringEncoding];
    unsigned char digest[CC_SHA256_DIGEST_LENGTH];
    CC_SHA256([raw bytes], [raw length], digest);

    [QTKeychain setData:secret forKey:@"secret"];
    [QTKeychain setData:[NSData dataWithBytes:digest length:sizeof(digest)] forKey:@"pin"];

    NSUserDefaults *d = [NSUserDefaults standardUserDefaults];
    [d setObject:[activationResult objectForKey:@"member_no"] forKey:@"member_no"];
    [d setObject:[activationResult objectForKey:@"first_name"] forKey:@"first_name"];
    [d setObject:[NSNumber numberWithUnsignedLongLong:0] forKey:@"counter"];
    [d setInteger:0 forKey:@"pin_failures"];
    [d synchronize];

    QTPassAppDelegate *app = (QTPassAppDelegate *)[[UIApplication sharedApplication] delegate];
    // Deferred: swapping the root releases this controller, so not while inside its own method
    [app performSelector:@selector(showPass) withObject:nil afterDelay:0.0];
}

- (BOOL)textFieldShouldReturn:(UITextField *)textField
{
    [self action:textField];
    return NO;
}

- (void)dealloc
{
    [connection cancel];
    [connection release];
    [responseData release];
    [activationResult release];
    [phoneField release];
    [codeField release];
    [pinField release];
    [infoLabel release];
    [actionButton release];
    [spinner release];
    [super dealloc];
}

@end
