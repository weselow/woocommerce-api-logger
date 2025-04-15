jQuery(document).ready(function ($) {

    function formatDate(timestamp) {
        const d = new Date(timestamp.replace('T', ' ').replace('_', ':').replace('Z', ''));
        if (isNaN(d)) return timestamp;
        return d.toLocaleString();
    }

    $('.woo-api-view-json').on('click', function () {
        const folder = $(this).data('folder');
        const type = $(this).data('type');
        const targetRow = $(`.woo-api-json-row[data-json="${folder}-${type}"]`);
        const targetPre = targetRow.find('.woo-api-json-content');

        if (targetPre.is(':visible')) {
            targetPre.slideUp();
            return;
        }

        if (targetPre.text().trim() === '') {
            $.post(WooApiLoggerAjax.ajaxurl, {
                action: 'woo_api_logger_load_json',
                nonce: WooApiLoggerAjax.nonce,
                folder: folder,
                type: type
            }, function (response) {
                if (response.success) {
                    targetPre.text(response.data);
                    targetPre.slideDown();
                } else {
                    targetPre.text('Ошибка загрузки: ' + response.data);
                    targetPre.slideDown();
                }
            });
        } else {
            targetPre.slideDown();
        }
    });

    $('.woo-api-delete-log').on('click', function () {
        if (!confirm('Удалить лог и связанные файлы?')) return;

        const folder = $(this).data('folder');
        const row = $(this).closest('tr');

        $.post(WooApiLoggerAjax.ajaxurl, {
            action: 'woo_api_logger_delete_log',
            nonce: WooApiLoggerAjax.nonce,
            folder: folder
        }, function (response) {
            if (response.success) {
                row.next('.woo-api-json-row[data-json="' + folder + '-request"]').remove();
                row.next('.woo-api-json-row[data-json="' + folder + '-response"]').remove();
                row.remove();
            } else {
                alert('Ошибка удаления: ' + response.data);
            }
        });
    });

    function reloadLogTable() {
        $.post(WooApiLoggerAjax.ajaxurl, {
            action: 'woo_api_logger_list_dirs',
            nonce: WooApiLoggerAjax.nonce
        }, function (response) {
            if (!response.success || !response.data) return;

            const existing = new Set();
            $('.woo-api-delete-log').each(function () {
                existing.add($(this).data('folder'));
            });

            response.data.forEach(log => {
                if (existing.has(log.folder)) return;

                const row = `
                <tr>
                    <td>${formatDate(log.timestamp)}</td>
                    <td>${log.method}</td>
                    <td>${log.route}</td>
                    <td>${log.duration} сек</td>
                    <td><button class="woo-api-view-json" data-type="request" data-folder="${log.folder}">🔍</button></td>
                    <td><button class="woo-api-view-json" data-type="response" data-folder="${log.folder}">🔍</button></td>
                    <td><button class="woo-api-delete-log" data-folder="${log.folder}">🗑</button></td>
                </tr>
                <tr class="woo-api-json-row" data-json="${log.folder}-request"><td colspan="7"><pre style="display:none" class="woo-api-json-content"></pre></td></tr>
                <tr class="woo-api-json-row" data-json="${log.folder}-response"><td colspan="7"><pre style="display:none" class="woo-api-json-content"></pre></td></tr>
                `;
                $('table.woocommerce-api-log-table tbody').prepend(row);
            });
        });
    }

    // Запускаем обновление каждые X сек, заданные в настройках
    setInterval(reloadLogTable, (WooApiLoggerAjax.refresh_interval || 30) * 1000);
});