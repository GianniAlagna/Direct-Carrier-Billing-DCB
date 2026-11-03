//
//  QTPassAppDelegate.m
//  QTPass - iPhone OS 2.x
//
//  Manual memory management (retain/release) - ARC does not exist yet.
//

#import "QTPassAppDelegate.h"
#import "QTKeychain.h"
#import "ActivationViewController.h"
#import "PassViewController.h"

@implementation QTPassAppDelegate

@synthesize window, navigationController;

- (void)applicationDidFinishLaunching:(UIApplication *)application
{
    [application setStatusBarStyle:UIStatusBarStyleBlackOpaque animated:NO];

    UIWindow *w = [[UIWindow alloc] initWithFrame:[[UIScreen mainScreen] bounds]];
    self.window = w;
    [w release];
    window.backgroundColor = [UIColor blackColor];

    if ([QTKeychain dataForKey:@"secret"] != nil) {
        [self showPass];
    } else {
        [self showActivation];
    }
    [window makeKeyAndVisible];
}

// setViewControllers:animated: only exists from iPhone OS 3.0, so in 2.x the
// root is swapped by replacing the navigation controller.
- (void)setRoot:(UIViewController *)vc
{
    [navigationController.view removeFromSuperview];
    UINavigationController *nav = [[UINavigationController alloc] initWithRootViewController:vc];
    nav.navigationBar.barStyle = UIBarStyleBlackOpaque;
    self.navigationController = nav;
    [nav release];
    [window addSubview:navigationController.view];
}

- (void)showPass
{
    PassViewController *vc = [[PassViewController alloc] init];
    [self setRoot:vc];
    [vc release];
}

- (void)showActivation
{
    ActivationViewController *vc = [[ActivationViewController alloc] init];
    [self setRoot:vc];
    [vc release];
}

- (void)applicationWillTerminate:(UIApplication *)application
{
    // No background execution in iPhone OS 2.x: the app is simply quit.
    [[NSUserDefaults standardUserDefaults] synchronize];
}

- (void)dealloc
{
    [navigationController release];
    [window release];
    [super dealloc];
}

@end
