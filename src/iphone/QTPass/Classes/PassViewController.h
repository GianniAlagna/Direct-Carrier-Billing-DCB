//
//  PassViewController.h
//  QTPass - iPhone OS 2.x
//
//  The door pass: app PIN -> full-screen one-time code on an animated
//  background in the colour of the night. Works without network.
//

#import <UIKit/UIKit.h>

@interface PassViewController : UIViewController <UITextFieldDelegate> {
    UITextField *pinField;
    UIView *passView;
    UILabel *codeLabel;
    UILabel *memberLabel;
    UILabel *timerLabel;
    UIButton *newCodeButton;

    NSTimer *pulseTimer;
    NSTimer *countdownTimer;
    int secondsLeft;
    float hue;
}

@end
