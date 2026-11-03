//
//  QTHotp.h
//  QTPass - iPhone OS 2.x
//
//  HOTP one-time codes (RFC 4226) computed on the device, fully offline.
//  Uses CommonCrypto (CCHmac), available since iPhone OS 2.0.
//

#import <Foundation/Foundation.h>

@interface QTHotp : NSObject {
}

// 6-digit code for a raw secret and counter value
+ (NSString *)codeWithSecret:(NSData *)secret counter:(unsigned long long)counter;

// RFC 3548 Base32 (the server ships the secret Base32-encoded inside a plist)
+ (NSData *)dataFromBase32String:(NSString *)base32;

@end
