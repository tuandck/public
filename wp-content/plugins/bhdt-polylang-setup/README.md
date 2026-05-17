# BHDT Polylang Setup Plugin

## Hướng Dẫn

Để tạo bản dịch tiếng Anh cho các bài viết và liên kết chúng via Polylang:

### Bước 1: Kích hoạt Plugin
1. Đăng nhập WordPress Admin
2. Vào **Plugins** → Tìm **"BHDT Polylang Setup"**
3. Nhấn **Activate** (Kích hoạt)

### Bước 2: Tạo Bản Dịch Tiếng Anh
1. Vào **Tools** → **BHDT Polylang Setup** (Công cụ → Cài đặt Polylang BHDT)
2. Nhấn nút **"Tạo Bản Dịch Tiếng Anh"**
3. Hệ thống sẽ:
   - Tạo bản nháp (Draft) tiếng Anh cho mỗi bài viết Việt
   - Tự động liên kết chúng qua Polylang
   - Hiển thị thống kê kết quả

### Bước 3: Chỉnh Sửa & Xuất Bản
1. Vào **Posts** (Bài viết) hoặc **Pages** (Trang)
2. Các bài viết tiếng Anh sẽ là **Draft** (Bản nháp)
3. Bạn có thể:
   - Chỉnh sửa nội dung để dịch sang tiếng Anh chính xác hơn
   - Hoặc xuất bản trực tiếp (nó sẽ sử dụng theme translations)

### Bước 4: Kiểm Tra Language Switcher
1. Vào trang web chính
2. Nhấn **EN** trong góc trên cùng
3. Nó sẽ:
   - Chuyển theme sang tiếng Anh (từ .po translations)
   - Nếu bài viết có bản tiếng Anh, sẽ hiển thị bản đó
   - Nếu chưa, sẽ hiển thị bản Việt gốc nhưng giao diện tiếng Anh

## File Translations

- **`languages/bhdt-wirecutter-en_US.po`** - Dịch tiếng Anh cho giao diện theme
- **`languages/bhdt-wirecutter-en_US.mo`** - File nhị phân (tự động tạo hoặc bạn có thể skip)

## Troubleshooting

**Q: Khi nhấn EN, giao diện vẫn tiếng Việt?**
A: Kiểm tra Polylang settings:
1. Vào **Polylang** → **Settings**
2. Đảm bảo "English" được kích hoạt
3. Kiểm tra theme language support

**Q: Bài viết tiếng Anh đâu rồi?**
A: Kiểm tra Posts → Tất cả bài viết, lọc by **Language: English**

**Q: Muốn thay đổi giao diện tiếng Anh?**
A: Chỉnh sửa file `bhdt-wirecutter-en_US.po` trong `languages/` folder

## Hỗ Trợ

Xem tài liệu Polylang: https://polylang.pro/doc/
