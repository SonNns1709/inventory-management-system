<!--
Quy trình GitHub Flow (tiêu đề Chương 7 §IV của báo cáo; tệp Word nhận được không có nội dung chi tiết của mục này
nên quy ước nhánh dưới đây chỉ là gợi ý — nhóm chỉnh lại cho khớp):
  nhánh từ main  ->  feature/<mã-Jira>-<mô-tả-ngắn>  ->  Pull Request  ->  CI xanh + 1 người review  ->  merge vào main
-->

## Mô tả

<!-- Thay đổi này làm gì, vì sao? -->

## Liên quan

- Công việc Jira: SCRUM-
- Use Case: UC__.__ (nếu có)
- Quy tắc nghiệp vụ liên quan: BR__.__-_ (nếu có)

## Loại thay đổi

- [ ] Tính năng mới
- [ ] Sửa lỗi
- [ ] Migration / thay đổi cơ sở dữ liệu
- [ ] Tài liệu / cấu hình / CI

## Checklist

- [ ] `php artisan test` chạy xanh
- [ ] `vendor/bin/pint --test` không báo lỗi định dạng
- [ ] Nếu có migration: đã thử `php artisan migrate:fresh --seed` và `migrate:rollback` trên MySQL
- [ ] Phương thức Service nào được cài đặt xong thì đã bỏ `NotImplementedException` và viết test tương ứng
- [ ] Màn hình / API đã cài đặt xong thì đổi `ready` thành `true` trong `config/navigation.php`
- [ ] Đã cập nhật `docs/TRACEABILITY.md` hoặc README nếu đổi hành vi
