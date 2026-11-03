//
//  QTKeychain.h
//  QTPass - iPhone OS 2.x
//
//  Thin wrapper over Keychain Services (Security.framework).
//  The HOTP secret and the hashed app PIN never go to NSUserDefaults.
//

#import <Foundation/Foundation.h>

@interface QTKeychain : NSObject {
}

+ (BOOL)setData:(NSData *)data forKey:(NSString *)key;
+ (NSData *)dataForKey:(NSString *)key;
+ (void)removeKey:(NSString *)key;

@end
