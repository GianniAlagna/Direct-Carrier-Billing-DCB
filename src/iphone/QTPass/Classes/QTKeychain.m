//
//  QTKeychain.m
//  QTPass - iPhone OS 2.x
//

#import "QTKeychain.h"
#import <Security/Security.h>

static NSString * const kQTService = @"ch.qtrecords.qtpass";

@implementation QTKeychain

+ (NSMutableDictionary *)queryForKey:(NSString *)key
{
    NSMutableDictionary *q = [NSMutableDictionary dictionary];
    [q setObject:(id)kSecClassGenericPassword forKey:(id)kSecClass];
    [q setObject:kQTService forKey:(id)kSecAttrService];
    [q setObject:key forKey:(id)kSecAttrAccount];
    return q;
}

+ (BOOL)setData:(NSData *)data forKey:(NSString *)key
{
    [self removeKey:key];
    NSMutableDictionary *q = [self queryForKey:key];
    [q setObject:data forKey:(id)kSecValueData];
    OSStatus status = SecItemAdd((CFDictionaryRef)q, NULL);
    return status == noErr;
}

+ (NSData *)dataForKey:(NSString *)key
{
    NSMutableDictionary *q = [self queryForKey:key];
    [q setObject:(id)kCFBooleanTrue forKey:(id)kSecReturnData];
    [q setObject:(id)kSecMatchLimitOne forKey:(id)kSecMatchLimit];

    NSData *result = nil;
    OSStatus status = SecItemCopyMatching((CFDictionaryRef)q, (CFTypeRef *)&result);
    if (status != noErr) {
        return nil;
    }
    return [result autorelease]; // Copy rule: we own it, hand back autoreleased
}

+ (void)removeKey:(NSString *)key
{
    SecItemDelete((CFDictionaryRef)[self queryForKey:key]);
}

@end
