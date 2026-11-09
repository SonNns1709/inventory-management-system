#!/usr/bin/env bash
# Sao lưu cơ sở dữ liệu MySQL đang chạy trong Docker Compose — ứng với mục "IV. Sao lưu và phục hồi dữ liệu" ở Chương 4
# của báo cáo (tệp Word nhận được chỉ có tiêu đề mục, chưa có nội dung nên quy trình dưới đây theo thông lệ).
#
#   ./scripts/db-backup.sh            # lưu vào thư mục backups/
#   ./scripts/db-backup.sh /duong/dan # lưu vào thư mục khác
#
# Nên đặt lịch chạy hằng ngày (cron) và chép tệp .sql.gz sang nơi lưu trữ khác máy chủ.
set -euo pipefail

OUT_DIR="${1:-backups}"
mkdir -p "$OUT_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
FILE="$OUT_DIR/inventory-$STAMP.sql.gz"

# --single-transaction: sao lưu nhất quán với InnoDB mà không khóa bảng khi hệ thống đang chạy
docker compose exec -T db sh -c \
    'exec mysqldump --single-transaction --routines --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
    | gzip > "$FILE"

echo "Đã sao lưu: $FILE"
