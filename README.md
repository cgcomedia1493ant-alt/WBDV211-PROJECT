# OLFU Antipolo - Lost & Found Web Application 🐾

A dedicated web platform for reporting and tracking lost and found belongings within the Our Lady of Fatima University - Antipolo Campus. Built with **PHP, MySQL, and Vanilla Frontend**.

---

## 👥 Project Team & Role Assignment

### 🎨 Frontend Team (`frontend/`, `index.php`, `report.php`)
* **Lead / UI Designer:** Cloue
* **Scope:** Responsive UI Layout, OLFU Green Theme, Forms, Filters para sa Antipolo campus, at Claim Modal popups.

### ⚙️ Backend Team (`backend/`, `database.sql`)
* **Lead / Database:** Kristine
* **API & Logic:** Cyrille
* **Scope:** MySQL Schema (`database.sql`), Database connection (`config.php`), CRUD operations (`add_item.php`, `get_items.php`, `claim_item.php`), at Photo Upload handling (`uploads/`).

---

## 🛠️ Tech Stack & Setup Comments (XAMPP)

1. I-clone o i-download ang repository inside the `htdocs` folder:
   `C:/xampp/htdocs/olfu-antipolo-lost-found`
2. Open ang **XAMPP Control Panel** at i-start ang **Apache** and **MySQL**.
3. Go to `http://localhost/phpmyadmin` and i-import ang `database.sql`.
4. Open ang browser and pumunta sa:
   `http://localhost/olfu-antipolo-lost-found/index.php`