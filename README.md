# מערכת ניהול ציוד — בית הספר לתקשורת
## Equipment Management System

---

## 🚀 הפעלה מהירה (Docker)

### דרישות מקדימות
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) מותקן ופועל
- פורט 8080 ו-3306 פנויים

### שלבי הפעלה

```bash
# 1. הורד / פתח את תיקיית הפרויקט
cd equipment-system

# 2. הפעל את כל השירותים
docker-compose up -d --build

# המתן כ-30 שניות לאתחול MySQL

# 3. כנס למערכת
http://localhost:8080
```

### כניסה ראשונה
| שדה | ערך |
|-----|-----|
| תעודת זהות | `000000000` |
| סיסמה | `Admin1234` |

---

## 📁 מבנה הפרויקט

```
equipment-system/
├── docker-compose.yml         ← הגדרות Docker
├── Dockerfile                 ← PHP 8.2 + Apache
├── config/
│   └── config.php             ← הגדרות אפליקציה
├── database/
│   ├── schema.sql             ← טבלאות DB
│   └── seed.sql               ← נתוני ברירת מחדל
├── public/                    ← DocumentRoot (Apache)
│   ├── index.php              ← Front Controller / Router
│   ├── .htaccess              ← URL Rewriting
│   ├── assets/
│   │   ├── css/app.css        ← עיצוב ראשי
│   │   ├── css/login.css      ← עיצוב כניסה
│   │   └── js/app.js          ← JavaScript
│   └── uploads/inventory/     ← תמונות פריטים
└── src/
    ├── Database.php           ← חיבור PDO
    ├── Middleware/
    │   └── Auth.php           ← ניהול sessions
    ├── Models/
    │   ├── UserModel.php      ← משתמשים
    │   └── InventoryModel.php ← מלאי
    ├── Controllers/
    │   ├── AuthController.php ← התחברות / יציאה
    │   └── InventoryController.php ← ניהול מלאי
    └── Views/
        ├── layouts/main.php   ← תבנית ראשית + סרגל צד
        ├── auth/login.php     ← מסך כניסה
        ├── dashboard/index.php← לוח בקרה
        ├── inventory/
        │   ├── index.php      ← רשימת מלאי
        │   ├── form.php       ← הוספה / עריכה
        │   └── removed.php    ← פריטים שהוצאו
        ├── stub.php           ← עמודים בפיתוח
        └── 404.php            ← שגיאה 404
```

---

## 🌐 כתובות ושירותים

| שירות | כתובת | פרטים |
|-------|-------|--------|
| **האפליקציה** | http://localhost:8080 | כניסה ראשית |
| **phpMyAdmin** | http://localhost:8081 | ניהול DB (user: equipment_user / equipment_pass) |
| **MySQL** | localhost:3306 | גישה ישירה |

---

## 📋 עמודים קיימים (שלב 1)

| עמוד | URL | גישה |
|------|-----|-------|
| כניסה | `/login` | כולם |
| לוח בקרה | `/dashboard` | כולם |
| מלאי ציוד | `/inventory` | מנהל |
| הוספת פריט | `/inventory/create` | מנהל |
| עריכת פריט | `/inventory/{id}/edit` | מנהל |
| הוצאת פריט | POST `/inventory/{id}/remove` | מנהל |
| שחזור פריט | POST `/inventory/{id}/restore` | מנהל |
| פריטים שהוצאו | `/inventory/removed` | מנהל |
| חיפוש ברקוד | `/inventory/barcode?barcode=X` | AJAX |

---

## 🔮 שלבים הבאים (לפי הסדר שתיאמנו)

1. **יומני הזמנות** (`/journals`) — לוח שנה לפי יומן וציוד
2. **הזמנות** (`/orders`, `/my-orders`) — יצירה, אישור, סטטוסים
3. **השאלות / החזרות** (`/loans`) — מעקב אופרטיבי
4. **ניהול משתמשים** (`/users`) — CRUD, תפקידים
5. **דוחות** (`/reports`) — ייצוא CSV, סטטיסטיקות

---

## 🔧 פקודות שימושיות

```bash
# עצירה
docker-compose down

# הפעלה מחדש (ללא מחיקת נתונים)
docker-compose restart

# איפוס מלא (מחיקת DB!)
docker-compose down -v
docker-compose up -d --build

# צפייה בלוגים
docker-compose logs -f app
docker-compose logs -f db

# כניסה ל-container
docker exec -it equipment_app bash
docker exec -it equipment_db mysql -u equipment_user -pequipment_pass equipment_db
```

---

## 🔐 אבטחה

- סיסמאות מוצפנות עם **bcrypt** (cost 12)
- נעילת חשבון אחרי **5 ניסיונות כושלים** (15 דקות)
- **Session timeout** אחרי 30 דקות חוסר פעילות
- **CSRF**: יש להוסיף tokens בשלב ה-production
- הפרדת roles: admin / student

---

## 📤 העלאת לוגו

שמור את קובץ הלוגו בנתיב:
```
public/assets/images/logo.png
```
ועדכן את `src/Views/layouts/main.php` להשתמש בו (`$logoPath = '/assets/images/logo.png'`).

---

## 📤 ייבוא מלאי מ-CSV

ניתן להעלות קובץ CSV של מלאי — הפונקציה תיבנה בשלב הבא.
פורמט שדות: `barcode, name, description, journal, brand, model, serial_number, location, condition_status, quantity`
