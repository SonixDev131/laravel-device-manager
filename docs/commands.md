# Các loại lệnh và xử lý lỗi trong UniLab

## 1. Các loại lệnh hỗ trợ

1. **SHUTDOWN**: Tắt máy tính

    - Params: `{ "delay": 60, "force": false }`

2. **RESTART**: Khởi động lại máy tính

    - Params: `{ "delay": 30, "force": false }`

3. **INSTALL**: Cài đặt phần mềm

    - Params: `{ "package": "vscode", "version": "latest" }`

4. **UPDATE**: Cập nhật hệ thống/phần mềm

    - Params: `{ "target": "system" }` hoặc `{ "target": "application", "name": "chrome" }`

5. **EXECUTE**: Thực thi lệnh/script

    - Params: `{ "command": "ipconfig /flushdns", "shell": "cmd" }`

6. **FIREWALL_ON**: Bật tường lửa

    - Params: `{ "profile": "all" }` (Tùy chọn, mặc định là tất cả)

7. **FIREWALL_OFF**: Tắt tường lửa

    - Params: `{ "profile": "all" }` (Tùy chọn, mặc định là tất cả)

8. **BLOCK_WEBSITE**: Chặn các trang web
    - Params: `{ "urls": ["facebook.com", "twitter.com"] }`
    - Yêu cầu: Định dạng URL không có protocol (http/https)
    - Mô tả: Chặn truy cập đến các trang web chỉ định trên tất cả các máy tính trong phòng.
      Người dùng có thể xem danh sách các trang web bị chặn thông qua sidebar bên phải giao diện.

9. **LOCK**: Khóa máy tính

    - Không yêu cầu tham số

10. **LOG_OUT**: Đăng xuất người dùng

    - Params: `{ "force": false }` (Tùy chọn)

11. **MESSAGE**: Gửi tin nhắn đến máy tính

    - Params: `{ "message": "Nội dung tin nhắn", "title": "Tiêu đề" }`

12. **SCREENSHOT**: Chụp ảnh màn hình

    - Params: `{ "save_locally": true }` (Tùy chọn)

13. **CUSTOM**: Thực thi chương trình tùy chỉnh
    - Params: `{ "program": "notepad.exe", "args": "C:\\file.txt" }`

## 2. Xử lý lỗi và phục hồi

1. **Mất kết nối**:

    - Agent sẽ lưu cache lệnh chưa hoàn thành
    - Tự động kết nối lại sau 30 giây và tiếp tục thực thi

2. **Lệnh thất bại**:

    - Thử lại tối đa 3 lần với các lệnh quan trọng
    - Ghi log chi tiết lỗi và gửi về server

3. **Token hết hạn**:
    - Tự động làm mới token khi gặp lỗi 401 Unauthorized
    - Quay lại quy trình đăng ký nếu không thể làm mới token
