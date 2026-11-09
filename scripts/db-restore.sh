#!/usr/bin/env bash
# Phục hồi cơ sở dữ liệu từ tệp sao lưu .sql.gz — ứng với mục "IV. Sao lưu và phục hồi dữ liệu" ở Chương 4 của báo cáo
# (tệp Word nhận được chỉ có tiêu đề mục, chưa có nội dung nên quy trình dưới đây theo thông lệ).
#
#   ./scripts/db-restore.sh backups/inventory-20261102-080000.sql.gz
#
# CẢNH BÁO: dữ liệu hiện tại của các bảng có trong tệp sao lưu sẽ bị ghi đè.
set -euo pipefail

FILE="${1:-}"

if [[ -z "$FILE" || ! -f "$FILE" ]]; then
    echo "Cách dùng: $0 <tệp-sao-lưu.sql.gz>" >&2
    exit 1
fi

read -r -p "Phục hồi từ '$FILE' sẽ ghi đè dữ liệu hiện tại. Tiếp tục? [y/N] " answer
if [[ "$answer" != "y" && "$answer" != "Y" ]]; then
    echo "Đã hủy."
    exit 0
fi

gunzip -c "$FILE" | docker compose exec -T db sh -c \
    'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'

echo "Đã phục hồi từ: $FILE"
