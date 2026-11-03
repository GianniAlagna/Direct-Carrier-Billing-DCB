# QTPass iPhone app (iPhone OS 2.x draft)

Native client for phase 2 of the roadmap. Written as it would have been in
autumn 2008 with **Xcode 3.1 / iPhone SDK 2.1** on Mac OS X 10.5.

| File | Purpose |
|---|---|
| `main.m` | Entry point with `NSAutoreleasePool` |
| `Info.plist` | Bundle `ch.qtrecords.qtpass`, minimum OS 2.1 |
| `Classes/QTPassAppDelegate.*` | Window, navigation, root switching (2.x-compatible) |
| `Classes/ActivationViewController.*` | P2: phone number → SMS code → secret into Keychain → app PIN |
| `Classes/PassViewController.*` | P5: PIN → offline HOTP code, full-screen pulsing pass, 90 s visibility |
| `Classes/QTHotp.*` | RFC 4226 via `CCHmac`, Base32 decoder |
| `Classes/QTKeychain.*` | Keychain Services wrapper (`SecItemAdd`/`SecItemCopyMatching`) |

## 2008 constraints applied

- Manual memory management (`retain`/`release`/`autorelease`, `dealloc`).
- UI built in code, no Interface Builder NIB (keeps the draft reviewable as text).
- `NSURLConnection` delegate networking; XML plist responses parsed with
  `NSPropertyListSerialization` (no JSON parser in the SDK).
- No push, no background tasks, no `setViewControllers:` (3.0), no
  `UIRequiredDeviceCapabilities` (3.0), no blocks/GCD (iOS 4).
- Code generation is **fully offline** — the club basement has no signal.

## Project setup (Xcode 3.1)

1. New Project → iPhone OS → Window-Based Application, name `QTPass`.
2. Replace generated files with the ones in this folder.
3. Add frameworks: `UIKit`, `Foundation`, `CoreGraphics`, `Security`.
   (`CommonCrypto` is part of `libSystem`, no extra framework needed.)
4. Delete `MainWindow.xib` and the `NSMainNibFile` entry (the window is created in code).
5. Code signing: Apple Developer Program certificate + provisioning profile.

**Status:** draft, never compiled against the 2008 SDK (the project was
discontinued before development). Treat it as a design specification in code.
