# UniLab - Hệ thống quản lý phòng máy thực hành

## Giới thiệu

UniLab là một hệ thống quản lý phòng máy thực hành cho trường đại học/cao đẳng, cung cấp khả năng giám sát và điều khiển máy tính từ xa thông qua giao diện web.

## Tài liệu chi tiết

1. [Kiến trúc hệ thống](docs/architecture.md) - Tổng quan kiến trúc và thiết kế Message Queue
2. [Quy trình hoạt động](docs/workflows.md) - Các luồng xử lý chính của hệ thống
3. [Cài đặt Agent](docs/agent-installation.md) - Thông tin về Installation Scripts
4. [API Reference](docs/api-reference.md) - Tài liệu về các API endpoints
5. [Các loại lệnh và xử lý lỗi](docs/commands.md) - Thông tin về các loại lệnh và cách xử lý lỗi
6. [Roadmap phát triển](docs/roadmap.md) - Kế hoạch phát triển của dự án

## Liên hệ

Để biết thêm thông tin, vui lòng liên hệ: [email@example.com](mailto:email@example.com)

---

- Xác thực và phân quyền (admin)
- Quản lý phòng máy (thêm, sửa, xóa phòng)
- Quản lý máy tính (thêm, sửa, xóa máy tính trong phòng)
- Xem danh sách máy tính đang hoạt động/không hoạt động
- Gửi lệnh khởi động/tắt máy từ xa
- Dashboard tổng quan trạng thái phòng máy
- Giao diện quản lý phòng/máy (chỉ hỗ trợ desktop)
