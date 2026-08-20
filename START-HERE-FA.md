# شروع سریع نسخه اصلاح‌شده جزیره

این نسخه جایگزین نسخه قبلی است و مشکلات متن‌های نامرتبط، ارتفاع ناهماهنگ کارت‌ها، صفحه خالی کاوش و استفاده نادرست از ویدیو را اصلاح می‌کند.

## اجرای محلی در XAMPP

پوشه پروژه را در این مسیر قرار دهید:

```text
C:\xampp\htdocs\jazireh-astronomy
```

سپس در Git Bash:

```bash
cd /c/xampp/htdocs/jazireh-astronomy/frontend
npm ci
npm run dev
```

آدرس سایت:

```text
http://localhost:5173
```

## بررسی نهایی

```bash
npm run lint
npm run build
```

## مهم‌ترین اصلاحات

- تمام متن‌های مربوط به طراحی، ریسپانسیو بودن، API و داده آزمایشی از صفحات عمومی حذف شدند.
- ویدیوی ارسال‌شده فقط در Hero صفحه اصلی به‌عنوان پس‌زمینه استفاده می‌شود.
- صفحات دیگر پس‌زمینه مشکی و سبک دارند.
- کارت‌های هم‌ردیف ارتفاع یکسان دارند.
- صفحه کاوش با نمایش تعاملی سبک و پایدار بازسازی شده و دیگر به WebGL وابسته نیست.
- وابستگی‌های Three.js حذف شده‌اند تا حجم و زمان بارگذاری کمتر شود.
- تصویر APOD پیش‌فرض فقط از نسخه باکیفیت استفاده می‌کند.
- لینک رسمی کانال در تمام CTAهای یوتیوب:

```text
https://www.youtube.com/@Jazireh
```

## فایل‌های رسانه اصلی

- `frontend/public/media/home-hero-stars.mp4`: پس‌زمینه Hero صفحه اصلی
- `frontend/public/media/home-hero-stars.jpg`: پوستر باکیفیت Hero
- `frontend/public/media/starfield-background.mp4`: ویدیوی کتابخانه ویدیو
- `frontend/public/media/space-cinematic.mp4`: ویدیوی کتابخانه ویدیو
- `frontend/public/media/carina-webb.webp`: تصویر باکیفیت APOD
- `frontend/public/brand/jazireh-logo.webp`: لوگوی بهینه وب
