//
//  QTHotp.m
//  QTPass - iPhone OS 2.x
//

#import "QTHotp.h"
#import <CommonCrypto/CommonHMAC.h>

@implementation QTHotp

+ (NSString *)codeWithSecret:(NSData *)secret counter:(unsigned long long)counter
{
    // 8-byte big-endian moving factor
    unsigned char msg[8];
    int i;
    for (i = 7; i >= 0; i--) {
        msg[i] = (unsigned char)(counter & 0xFF);
        counter >>= 8;
    }

    unsigned char hash[CC_SHA1_DIGEST_LENGTH];
    CCHmac(kCCHmacAlgSHA1, [secret bytes], [secret length], msg, sizeof(msg), hash);

    // Dynamic truncation (RFC 4226, 5.3)
    int offset = hash[19] & 0x0F;
    unsigned int binary = ((hash[offset]     & 0x7F) << 24)
                        | ((hash[offset + 1] & 0xFF) << 16)
                        | ((hash[offset + 2] & 0xFF) << 8)
                        |  (hash[offset + 3] & 0xFF);

    return [NSString stringWithFormat:@"%06u", binary % 1000000];
}

+ (NSData *)dataFromBase32String:(NSString *)base32
{
    static const char *alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
    NSMutableData *out = [NSMutableData data];
    unsigned int buffer = 0;
    int bitsLeft = 0;
    NSUInteger i;

    NSString *clean = [[base32 uppercaseString] stringByReplacingOccurrencesOfString:@"=" withString:@""];
    for (i = 0; i < [clean length]; i++) {
        char c = (char)[clean characterAtIndex:i];
        const char *p = strchr(alphabet, c);
        if (p == NULL) {
            return nil;
        }
        buffer = (buffer << 5) | (unsigned int)(p - alphabet);
        bitsLeft += 5;
        if (bitsLeft >= 8) {
            unsigned char byte = (unsigned char)((buffer >> (bitsLeft - 8)) & 0xFF);
            [out appendBytes:&byte length:1];
            bitsLeft -= 8;
        }
    }
    return out;
}

@end
