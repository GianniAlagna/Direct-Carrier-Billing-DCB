//
//  QTPassAppDelegate.h
//  QTPass - iPhone OS 2.x (Xcode 3.1, iPhone SDK 2.1)
//

#import <UIKit/UIKit.h>

#define QT_API_BASE @"https://pass.qtpass.example/api"

@interface QTPassAppDelegate : NSObject <UIApplicationDelegate> {
    UIWindow *window;
    UINavigationController *navigationController;
}

@property (nonatomic, retain) UIWindow *window;
@property (nonatomic, retain) UINavigationController *navigationController;

- (void)showPass;
- (void)showActivation;

@end
