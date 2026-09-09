# Annamrita Setu – App Logo Guide

Apna logo lagane ke liye **ye files replace karo** (same name rakho, sirf image change karo):

## 📁 Folder: `assets/images/`

| File name | Kahan dikhta hai | Recommended size |
|-----------|------------------|------------------|
| **icon.png** | App icon (home screen) – general | **1024×1024 px** |
| **splash-icon.png** | Splash screen (app open hote hi) | **200–400 px** width, transparent/simple |
| **android-icon-foreground.png** | Android app icon (foreground) | **1024×1024 px**, transparent background |
| **android-icon-background.png** | Android icon background color/pattern | **1024×1024 px** (ya solid color) |
| **android-icon-monochrome.png** | Android themed icon (optional) | **1024×1024 px** |
| **favicon.png** | Web favicon (agar web use karo) | **48×48** ya **32×32 px** |

## ✅ Steps

1. Apna logo image ready karo (PNG, transparent background better).
2. **icon.png** – isi file ko overwrite karo (ya apne logo ka naam `icon.png` rakh ke `assets/images/` mein copy karo).
3. **splash-icon.png** – splash screen ke liye same logo (chota size OK, e.g. 200–400 px wide).
4. Android ke liye:
   - **android-icon-foreground.png** – logo with transparent background (1024×1024).
   - **android-icon-background.png** – background (solid color ya simple design).
5. Native build ke baad icon update dikhega. Agar pehle build ho chuka hai to:
   ```bash
   npx expo prebuild --clean
   npx expo run:android
   ```
   (iOS ke liye bhi prebuild/run karo.)

## Config (change mat karo agar path same ho)

Sab paths `app.json` mein already set hain:
- `icon` → `./assets/images/icon.png`
- `splash` → `./assets/images/splash-icon.png`
- Android adaptive icon → `android-icon-foreground.png` + `android-icon-background.png`

Agar tum **alag file name** use karna chahte ho (e.g. `mylogo.png`), to `app.json` mein ye paths update karo.
