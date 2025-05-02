jQuery(document).ready(function ($) {
    function reloadLogTable() {
        $.post(WooApiLoggerAjax.ajaxurl, {
            action: 'woo_api_logger_list_dirs',
            nonce: WooApiLoggerAjax.nonce
        }, function (response) {
            if (!response.success) return;

            const tbody = $('table.woocommerce-api-log-table tbody');
            tbody.empty();

            response.data.forEach(log => {
                let batchInfo = '';
                if (log.route === '/wc/v3/products/batch' && log.counts) {
                    batchInfo = `<br><small>
                        <span title="Создание">🆕 ${log.counts.create}</span>, 
                        <span title="Обновление">✏️ ${log.counts.update}</span>, 
                        <span title="Удаление">❌ ${log.counts.delete}</span>
                    </small>`;
                }

                const row = `
                <tr>
                    <td>${log.timestamp}</td>
                    <td>${log.method}</td>
                    <td>${log.route}${batchInfo}</td>
                    <td>${log.duration} сек</td>
                    <td><button class="woo-api-view-json" data-type="request" data-folder="${log.folder}">🔍</button></td>
                    <td><button class="woo-api-view-json" data-type="response" data-folder="${log.folder}">🔍</button></td>
                    <td><button class="woo-api-delete-log" data-folder="${log.folder}">🗑</button></td>
                </tr>
                <tr class="woo-api-json-row" data-json="${log.folder}-request"><td colspan="7"><pre style="display:none" class="woo-api-json-content"></pre></td></tr>
                <tr class="woo-api-json-row" data-json="${log.folder}-response"><td colspan="7"><pre style="display:none" class="woo-api-json-content"></pre></td></tr>`;
                tbody.append(row);
            });
        });
    }

    setInterval(reloadLogTable, (WooApiLoggerAjax.refresh_interval || 30) * 1000);

    $(document).on('click', '.woo-api-view-json', function () {
        const folder = $(this).data('folder');
        const type = $(this).data('type');
        const targetRow = $(`.woo-api-json-row[data-json="${folder}-${type}"]`);
        const targetPre = targetRow.find('.woo-api-json-content');
        if (targetPre.is(':visible')) return targetPre.slideUp();
        if (targetPre.text().trim() === '') {
            $.post(WooApiLoggerAjax.ajaxurl, {
                action: 'woo_api_logger_load_json',
                nonce: WooApiLoggerAjax.nonce,
                folder: folder,
                type: type
            }, function (response) {
                targetPre.text(response.data).slideDown();
            });
        } else {
            targetPre.slideDown();
        }
    });

    $(document).on('click', '.woo-api-delete-log', function () {
        if (!confirm('Удалить лог и связанные файлы?')) return;
        const folder = $(this).data('folder');
        const row = $(this).closest('tr');
        $.post(WooApiLoggerAjax.ajaxurl, {
            action: 'woo_api_logger_delete_log',
            nonce: WooApiLoggerAjax.nonce,
            folder: folder
        }, function () {
            row.next().remove();
            row.next().remove();
            row.remove();
        });
    });
});
