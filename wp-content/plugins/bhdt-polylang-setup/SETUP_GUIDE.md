# Hướng Dẫn Cài Đặt Chuyển Ngôn Ngữ (VI ↔ EN)

## 📋 Kiểm Tra Tiên Quyết

Hãy đảm bảo các thứ này đã sẵn sàng:

✅ **Polylang Plugin** - Đã kích hoạt  
✅ **Vietnamese (VI)** - Là ngôn ngữ mặc định  
✅ **English (EN)** - Đã thêm  

## 🚀 Bước 1: Kích Hoạt Plugin BHDT Polylang Setup

1. Vào **WordPress Admin** → **Plugins** (Plugin)
2. Tìm **"BHDT Polylang Setup"**
3. Nhấn **Activate** (Kích hoạt)
4. Bạn sẽ thấy nó xuất hiện trong sidebar

## 📝 Bước 2: Tạo Bài Viết Tiếng Anh

1. Vào **Tools** (Công cụ) → **BHDT Polylang Setup**
2. Nhấn nút **"Create English Translations"** (Tạo Bài Viết Tiếng Anh)
3. Chờ xong sẽ thấy:
   - ✓ Tạo mới: X bài viết
   - ✓ Đã liên kết: Y bài viết
   - ✓ Lỗi: Z

## 🌐 Bước 3: Test Language Switcher

### **Test Tại Frontend (Trang Web)**
1. Vào trang chủ website
2. Nhấn **EN** ở góc trên cùng
3. **Expected Result:** Giao diện đổi sang tiếng Anh
   - Menu: "Bài đánh giá" → "Reviews"
   - Button: "Đăng nhập" → "Log in"
   - Sidebar: "Mới nhất" → "The Latest"

4. Nhấn **VI** → Quay lại tiếng Việt

### **Troubleshooting**

**Q: Nhấn EN vẫn toàn tiếng Việt?**

**A: Thử các cách này:**
1. **Clear Browser Cache**
   - Bấm **Ctrl + Shift + Delete** (hoặc Cmd + Shift + Delete trên Mac)
   - Xóa "Cache" và "Cookies"
   - Reload trang

2. **Check Polylang Settings**
   - Vào **Polylang** → **Settings**
   - Kiểm tra:
     - ✓ Vietnamese có `[vi]` flag
     - ✓ English có `[en]` flag
     - ✓ Cả hai ngôn ngữ đều "Active"

3. **Check Language Switcher URL**
   - Khi bạn nhấn EN, URL có thay đổi không?
   - Ví dụ: `banhangdientu.local/en/` ?
   - Nếu có → Polylang hoạt động ✓

4. **Check WordPress Language Settings**
   - Vào **Settings** (Cài đặt) → **General** (Chung)
   - **Site Language** nên là **Việt Nam** (VI)

## 🎯 Kết Quả Cuối Cùng

Khi hoàn thành, website sẽ có:

| Phần Tử | Tiếng Việt | Tiếng Anh |
|---------|-----------|----------|
| Menu | "Bài đánh giá" | "Reviews" |
| Button | "Đăng nhập" | "Log in" |
| Sidebar | "Mới nhất" | "The Latest" |
| Bài Viết | Nội dung Việt | Bản nháp Anh |

## 📚 File Translations

**Theme translations được lưu tại:**
```
/wp-content/themes/bhdt-wirecutter/languages/
├── bhdt-wirecutter-en_US.po  (Human-readable)
└── bhdt-wirecutter-en_US.mo  (Binary compiled)
```

**Nếu bạn muốn chỉnh sửa translations:**
1. Mở file `.po` bằng text editor
2. Tìm `msgid "..."` (tiếng Việt)
3. Sửa `msgstr "..."` (tiếng Anh)
4. Save file
5. Compile lại `.mo` (hoặc WP sẽ tự dùng .po)

## ✅ Hoàn Tất!

Nếu tất cả hoạt động, bạn đã thành công! 🎉

**Bây giờ người dùng có thể:**
- 🇻🇳 Xem tiếng Việt (mặc định)
- 🇬🇧 Nhấn EN để xem tiếng Anh
- 🔄 Chuyển đổi tự do
