$(document).ready(function () {
    $('#customers').DataTable({
        bLengthChange: false,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']]
    });

    function toast(msg, ok = true) {
        var $m = $('<div/>', {
            text: msg,
            css: {
                position: 'fixed',
                bottom: '20px',
                left: '50%',
                transform: 'translateX(-50%)',
                backgroundColor: ok ? 'rgba(40,167,69,0.95)' : 'rgba(220,53,69,0.95)',
                color: '#fff',
                padding: '10px 14px',
                borderRadius: '6px',
                zIndex: 9999,
                boxShadow: '0 6px 20px rgba(0,0,0,0.2)'
            }
        }).appendTo('body');
        setTimeout(function(){ $m.fadeOut(200, function(){ $(this).remove(); }); }, 1200);
    }

    document.addEventListener('change', function (e) {
        const el = e.target;
        if (!el.classList.contains('js-change-group')) return;

        const idCustomer = el.dataset.idCustomer;
        const idGroup    = el.value;
        const selCell    = el.closest('td');
        const tr         = el.closest('tr');
        const badge      = tr ? tr.querySelector('.status-badge') : null;
        const prev       = el.getAttribute('data-prev') || '';
        const proId      = String(window.PRO_GROUP_ID || document.body.getAttribute('data-pro-id') || '');
        const protiId    = String(window.PROTI_GROUP_ID || document.body.getAttribute('data-proti-id') || '');

        el.disabled = true;

        fetch(ajax_change_group_url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: new URLSearchParams({
                ajax: 1,
                action: 'changeCustomerGroup',
                id_customer: idCustomer,
                id_group: idGroup
            })
        })
            .then(r => r.json())
            .then(json => {
                if (json && json.success) {
                    el.setAttribute('data-prev', idGroup);
                    toast('Type de compte mis à jour ✔️', true);

                    if (tr) {
                        tr.classList.remove('row-pro', 'row-proticulier');
                        if (String(idGroup) === proId) tr.classList.add('row-pro');
                        else tr.classList.add('row-proticulier');
                    }

                    if (badge) {
                        if (String(idGroup) === proId) {
                            badge.textContent = 'Professionnel';
                            badge.classList.remove('badge-proti');
                            badge.classList.add('badge-pro');
                        } else {
                            badge.textContent = 'Proticulier';
                            badge.classList.remove('badge-pro');
                            badge.classList.add('badge-proti');
                        }
                    }

                    if (selCell) {
                        const small = selCell.querySelector('.small-muted');
                        if (small) {
                            const label = el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : '';
                            small.textContent = 'Groupe par défaut : ' + (label || '—');
                        }
                    }
                } else {
                    toast((json && json.message) ? json.message : 'Échec mise à jour', false);
                    el.value = prev;
                }
            })
            .catch(() => {
                toast('Erreur réseau', false);
                el.value = prev;
            })
            .finally(() => { el.disabled = false; });
    });

    $(".file-download").on("click", function() {
        let fileName = $(this).attr('data-file-name');
        let fileExtension = $(this).attr('data-type');
        var xhrOverride = new XMLHttpRequest();
        xhrOverride.responseType = 'arraybuffer';
        if(fileName.length > 0) {
            $.ajax({
                type: "POST",
                url: ajax_download_file_url,
                data: {
                    controller : 'AdminEspacePro',
                    "fileName": fileName,
                    action: "DownloadFile",
                    ajax : true
                },
                xhr: function() { return xhrOverride; },
                success: function(responseData){
                    var binaryData = [];
                    binaryData.push(responseData);
                    var blob = new Blob(binaryData, {type: "application/"+fileExtension})
                    const url = window.URL.createObjectURL(blob)
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', fileName);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }
            })
        }
    });

});
