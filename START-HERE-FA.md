# شروع سریع مخزن جزیره

اگر می‌خواهید خیلی سریع ساختار و ارزش فنی این ریپازیتوری را بفهمید، این ترتیب بهترین نقطه شروع است:

1. [README.md](./README.md) برای معرفی بین‌المللی و نمای کلی مهندسی
2. [README.fa.md](./README.fa.md) برای نسخه فارسی همان روایت
3. [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) برای معماری فعلی
4. [docs/API.md](./docs/API.md) برای REST API سفارشی
5. [docs/ROADMAP.md](./docs/ROADMAP.md) برای مرز روشن بین وضعیت فعلی و برنامه آینده

## این مخزن چه چیزی را نگه می‌دارد

این ریپازیتوری فقط بخش‌های متعلق به خود پروژه را شامل می‌شود:

- فرانت‌اند React
- قالب سفارشی WordPress
- افزونه سفارشی WordPress
- ابزار بیرونی Community sync

WordPress core، دیتابیس، uploads و secretهای محیط اجرا عمدا داخل ریپازیتوری نیستند.

## الگوی توسعه محلی

نمونه‌ای از ساختار محیط فعلی نویسنده:

```text
سورس پروژه: C:\xampp\htdocs\jazireh-github
WordPress runtime: C:\xampp\htdocs\wordpress
```

ساخت فرانت‌اند:

```bash
cd frontend
npm ci
npm run build
npm run build:wordpress
```

## نکته مهم

این مخزن برای بررسی فنی عمومی و ارائه نمونه‌کار آماده شده است. برای استقرار واقعی، باید WordPress runtime، دیتابیس، uploads، تنظیمات تولید و worker همگام‌سازی Jazireh Daily را جداگانه فراهم کنید.
