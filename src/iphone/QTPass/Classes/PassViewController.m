//
//  PassViewController.m
//  QTPass - iPhone OS 2.x
//
//  Flow (docs/03 P5):
//   1. guest enters app PIN (5 wrong -> secret wiped, re-activation needed)
//   2. counter := counter + 1, code := HOTP(secret, counter)  [offline]
//   3. full-screen pass, colour pulse makes a static screenshot obvious
//   4. after 90 s the code is hidden; "New code" generates the next one
//
//  The door accepts codes within a look-ahead window of 10 (online) or the
//  next 20 cached codes (offline venue node), so unused codes do not lock
//  the guest out.
//

#import "PassViewController.h"
#import "QTPassAppDelegate.h"
#import "QTKeychain.h"
#import "QTHotp.h"
#import <CommonCrypto/CommonDigest.h>

#define QT_CODE_VISIBLE_SECONDS 90
#define QT_MAX_PIN_FAILURES     5

@interface PassViewController (Private)
- (void)showPinEntry;
- (BOOL)checkPin:(NSString *)pin;
- (void)generateCode;
- (void)stopTimers;
@end

@implementation PassViewController

- (void)loadView
{
    UIView *v = [[UIView alloc] initWithFrame:[[UIScreen mainScreen] applicationFrame]];
    v.backgroundColor = [UIColor blackColor];
    self.view = v;
    [v release];
    self.title = @"QT Pass";

    pinField = [[UITextField alloc] initWithFrame:CGRectMake(60, 60, 200, 50)];
    pinField.borderStyle = UITextBorderStyleRoundedRect;
    pinField.font = [UIFont boldSystemFontOfSize:32];
    pinField.textAlignment = UITextAlignmentCenter;
    pinField.placeholder = @"PIN";
    pinField.secureTextEntry = YES;
    pinField.keyboardType = UIKeyboardTypeNumberPad;
    pinField.delegate = self;
    [self.view addSubview:pinField];

    // Number pad has no return key in 2.x: react on text change instead
    [pinField addTarget:self action:@selector(pinChanged:) forControlEvents:UIControlEventEditingChanged];

    passView = [[UIView alloc] initWithFrame:self.view.bounds];
    passView.hidden = YES;
    [self.view addSubview:passView];

    UILabel *brand = [[UILabel alloc] initWithFrame:CGRectMake(0, 30, 320, 40)];
    brand.text = @"Q T   P A S S";
    brand.font = [UIFont boldSystemFontOfSize:28];
    brand.textAlignment = UITextAlignmentCenter;
    brand.textColor = [UIColor whiteColor];
    brand.backgroundColor = [UIColor clearColor];
    [passView addSubview:brand];
    [brand release];

    codeLabel = [[UILabel alloc] initWithFrame:CGRectMake(0, 120, 320, 90)];
    codeLabel.font = [UIFont boldSystemFontOfSize:72];
    codeLabel.textAlignment = UITextAlignmentCenter;
    codeLabel.textColor = [UIColor whiteColor];
    codeLabel.backgroundColor = [UIColor clearColor];
    codeLabel.shadowColor = [UIColor blackColor];
    codeLabel.shadowOffset = CGSizeMake(2, 2);
    [passView addSubview:codeLabel];

    memberLabel = [[UILabel alloc] initWithFrame:CGRectMake(0, 220, 320, 60)];
    memberLabel.numberOfLines = 2;
    memberLabel.font = [UIFont systemFontOfSize:22];
    memberLabel.textAlignment = UITextAlignmentCenter;
    memberLabel.textColor = [UIColor whiteColor];
    memberLabel.backgroundColor = [UIColor clearColor];
    [passView addSubview:memberLabel];

    timerLabel = [[UILabel alloc] initWithFrame:CGRectMake(0, 290, 320, 24)];
    timerLabel.font = [UIFont systemFontOfSize:14];
    timerLabel.textAlignment = UITextAlignmentCenter;
    timerLabel.textColor = [UIColor colorWithWhite:1.0 alpha:0.8];
    timerLabel.backgroundColor = [UIColor clearColor];
    [passView addSubview:timerLabel];

    newCodeButton = [[UIButton buttonWithType:UIButtonTypeRoundedRect] retain];
    newCodeButton.frame = CGRectMake(60, 340, 200, 44);
    [newCodeButton setTitle:@"New code" forState:UIControlStateNormal];
    [newCodeButton addTarget:self action:@selector(newCode:) forControlEvents:UIControlEventTouchUpInside];
    [passView addSubview:newCodeButton];
}

- (void)viewWillAppear:(BOOL)animated
{
    [super viewWillAppear:animated];
    [self showPinEntry];
}

- (void)viewWillDisappear:(BOOL)animated
{
    [self stopTimers];
    [super viewWillDisappear:animated];
}

#pragma mark PIN

- (void)showPinEntry
{
    [self stopTimers];
    passView.hidden = YES;
    pinField.hidden = NO;
    pinField.text = @"";
    [pinField becomeFirstResponder];
    [UIApplication sharedApplication].idleTimerDisabled = NO;
}

- (void)pinChanged:(id)sender
{
    if ([pinField.text length] < 4) {
        return;
    }
    if ([self checkPin:pinField.text]) {
        [pinField resignFirstResponder];
        pinField.hidden = YES;
        [self generateCode];
    } else {
        pinField.text = @"";
    }
}

- (BOOL)checkPin:(NSString *)pin
{
    NSUserDefaults *d = [NSUserDefaults standardUserDefaults];
    NSString *salted = [NSString stringWithFormat:@"%@:%@", [d objectForKey:@"member_no"], pin];
    NSData *raw = [salted dataUsingEncoding:NSUTF8StringEncoding];
    unsigned char digest[CC_SHA256_DIGEST_LENGTH];
    CC_SHA256([raw bytes], [raw length], digest);
    NSData *given = [NSData dataWithBytes:digest length:sizeof(digest)];

    if ([given isEqualToData:[QTKeychain dataForKey:@"pin"]]) {
        [d setInteger:0 forKey:@"pin_failures"];
        return YES;
    }

    NSInteger failures = [d integerForKey:@"pin_failures"] + 1;
    [d setInteger:failures forKey:@"pin_failures"];
    [d synchronize];

    if (failures >= QT_MAX_PIN_FAILURES) {
        [QTKeychain removeKey:@"secret"];
        [QTKeychain removeKey:@"pin"];
        UIAlertView *a = [[UIAlertView alloc] initWithTitle:@"Pass locked"
            message:@"Too many wrong PINs. Please activate your phone again." delegate:nil
            cancelButtonTitle:@"OK" otherButtonTitles:nil];
        [a show];
        [a release];
        QTPassAppDelegate *app = (QTPassAppDelegate *)[[UIApplication sharedApplication] delegate];
        [app performSelector:@selector(showActivation) withObject:nil afterDelay:0.0];
    }
    return NO;
}

#pragma mark Code

- (void)generateCode
{
    NSData *secret = [QTKeychain dataForKey:@"secret"];
    if (secret == nil) {
        return;
    }
    NSUserDefaults *d = [NSUserDefaults standardUserDefaults];
    unsigned long long counter = [[d objectForKey:@"counter"] unsignedLongLongValue] + 1;
    [d setObject:[NSNumber numberWithUnsignedLongLong:counter] forKey:@"counter"];
    [d synchronize]; // persist before display: never show the same code twice

    NSString *code = [QTHotp codeWithSecret:secret counter:counter];
    codeLabel.text = [NSString stringWithFormat:@"%@ %@",
                      [code substringToIndex:3], [code substringFromIndex:3]];
    memberLabel.text = [NSString stringWithFormat:@"%@\nMember %@",
                        [d objectForKey:@"first_name"], [d objectForKey:@"member_no"]];

    passView.hidden = NO;
    secondsLeft = QT_CODE_VISIBLE_SECONDS;
    timerLabel.text = [NSString stringWithFormat:@"Show at the door - %d s", secondsLeft];

    // Keep screen on and bright while the pass is shown
    [UIApplication sharedApplication].idleTimerDisabled = YES;

    [self stopTimers];
    pulseTimer = [[NSTimer scheduledTimerWithTimeInterval:0.05 target:self
                    selector:@selector(pulse:) userInfo:nil repeats:YES] retain];
    countdownTimer = [[NSTimer scheduledTimerWithTimeInterval:1.0 target:self
                    selector:@selector(tick:) userInfo:nil repeats:YES] retain];
}

- (void)pulse:(NSTimer *)t
{
    // Slowly rotating hue: a screenshot is visibly frozen next to a live screen
    hue += 0.004f;
    if (hue > 1.0f) {
        hue -= 1.0f;
    }
    passView.backgroundColor = [UIColor colorWithHue:hue saturation:0.9f brightness:0.8f alpha:1.0f];
}

- (void)tick:(NSTimer *)t
{
    secondsLeft--;
    timerLabel.text = [NSString stringWithFormat:@"Show at the door - %d s", secondsLeft];
    if (secondsLeft <= 0) {
        [self showPinEntry];
    }
}

- (void)newCode:(id)sender
{
    [self generateCode];
}

- (void)stopTimers
{
    [pulseTimer invalidate];
    [pulseTimer release];
    pulseTimer = nil;
    [countdownTimer invalidate];
    [countdownTimer release];
    countdownTimer = nil;
}

- (void)dealloc
{
    [self stopTimers];
    [pinField release];
    [passView release];
    [codeLabel release];
    [memberLabel release];
    [timerLabel release];
    [newCodeButton release];
    [super dealloc];
}

@end
