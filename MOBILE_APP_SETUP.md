# Mobile App Download Feature

## Overview
I've added a **Direct APK Download** feature to your Bayawan Bai Hotel website. Users can now download your Android app directly from the website.

## What Was Added

### 1. Footer Download Button (`includes/footer.php`)
- Added a "Get Our Mobile App" section in the footer
- Green Android download button with hover effects
- Shows on all pages of your website

### 2. Dedicated Download Page (`download-app.php`)
- Beautiful landing page at `/bayawanhotel/download-app.php`
- App features showcase (Easy Booking, Food Ordering, Digital Key)
- Download button with proper Android styling
- QR code for easy mobile access
- Installation instructions for users
- "Coming Soon" mode when APK is not available

### 3. Secure Download Handler
- PHP script handles file serving with proper headers
- Prevents direct access to APK file
- Forces download on Android devices

## How to Add Your APK File

1. **Build or obtain your Android app APK file**
   - File should be named: `bayawanbai-hotel-app.apk`
   - Or edit `download-app.php` line 11 to match your filename

2. **Upload the APK file**
   ```
   Copy your APK file to: /bayawanhotel/apps/
   ```

3. **Verify it works**
   - Visit: `http://localhost/bayawanhotel/download-app.php`
   - Click "Download APK" button
   - Test on an Android device

## File Structure
```
bayawanhotel/
├── apps/
│   └── bayawanbai-hotel-app.apk    ← Place your APK here
├── download-app.php                  ← Download page
└── includes/
    └── footer.php                    ← Updated with download button
```

## Customization Options

### Change APK Filename
Edit `download-app.php` line 11:
```php
$apkFile = __DIR__ . '/apps/your-app-name.apk';
```

### Update App Version/Size
Edit `download-app.php` lines 12-13:
```php
$apkVersion = '1.0.0';
$apkSize = '15 MB';
```

### Change Button Colors
The button uses Android's brand colors (#3DDC84 green). Edit in:
- `includes/footer.php` line 85
- `download-app.php` line 88

## User Flow

1. User visits your website on mobile/desktop
2. Sees "Download APK" button in footer or visits `/download-app.php`
3. Clicks button → APK downloads
4. On Android: Opens file → Allows "Unknown Sources" → Installs
5. App is ready to use!

## Security Notes

- APK downloads only work when file exists in `/apps/` folder
- If no APK exists, page shows "Coming Soon" with email signup
- Download uses proper MIME type (`application/vnd.android.package-archive`)
- Headers prevent caching and force file download

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Download button not showing | Check that `apps/` folder exists |
| "File not found" error | Verify APK filename matches in `download-app.php` |
| Can't install on Android | Enable "Install from Unknown Sources" in Settings |
| Button styling off | Clear browser cache (CSS updated) |

## Next Steps

1. Add your APK file to the `/apps/` folder
2. Test the download on an Android device
3. Consider creating a PWA version for iOS users
4. Add app screenshots to the download page

## iOS Support

Currently, the feature only supports Android APK downloads. For iOS:
- Upload to Apple App Store (requires developer account)
- Or create a Progressive Web App (PWA) version
- The download page already shows "iOS version coming soon" message
