//
//  ActivationViewController.h
//  QTPass - iPhone OS 2.x
//
//  P2 device activation: mobile number -> SMS code -> secret into Keychain.
//

#import <UIKit/UIKit.h>

@interface ActivationViewController : UIViewController <UITextFieldDelegate> {
    UITextField *phoneField;
    UITextField *codeField;
    UITextField *pinField;
    UILabel *infoLabel;
    UIButton *actionButton;
    UIActivityIndicatorView *spinner;

    NSURLConnection *connection;
    NSMutableData *responseData;
    int step; // 0 = phone, 1 = SMS code, 2 = choose app PIN
    NSDictionary *activationResult;
}

@property (nonatomic, retain) NSURLConnection *connection;
@property (nonatomic, retain) NSMutableData *responseData;
@property (nonatomic, retain) NSDictionary *activationResult;

@end
