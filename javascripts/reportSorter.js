/**
 * ReportSorter - drag-to-reorder reports on Matomo standard report pages.
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    'use strict';

    // ── Bootstrap: wait for jQuery ────────────────────────────────────────────

    var MAX_WAIT = 10000; // ms
    var waited   = 0;
    var interval = setInterval(function () {
        waited += 100;
        if (typeof jQuery !== 'undefined') {
            clearInterval(interval);
            bootReportSorter(jQuery);
        } else if (waited >= MAX_WAIT) {
            clearInterval(interval);
        }
    }, 100);

    // ── Main module ───────────────────────────────────────────────────────────

    function bootReportSorter($) {

        var state = {
            category:    null,
            subcategory: null,
            idSite:      null,
            pageKey:     null   // category + '|' + subcategory
        };

        // ── URL helpers ───────────────────────────────────────────────────────

        function getHashParam(name) {
            // Use Matomo's own broadcast module if available
            if (window.broadcast && window.broadcast.getValueFromHash) {
                return window.broadcast.getValueFromHash(name, window.location.href) || '';
            }
            // Fallback: manual hash parse
            var hash = window.location.hash.replace(/^[#/?]+/, '');
            var re   = new RegExp('(?:^|&)' + name + '=([^&]*)');
            var m    = hash.match(re);
            return m ? decodeURIComponent(m[1]) : '';
        }

        function getQueryParam(name) {
            if (window.broadcast && window.broadcast.getValueFromUrl) {
                return window.broadcast.getValueFromUrl(name, window.location.search) || '';
            }
            var re = new RegExp('[?&]' + name + '=([^&]*)');
            var m  = window.location.search.match(re);
            return m ? decodeURIComponent(m[1]) : '';
        }

        function readCurrentPage() {
            var category    = getHashParam('category')    || getQueryParam('category');
            var subcategory = getHashParam('subcategory') || getQueryParam('subcategory');
            var idSite      = getQueryParam('idSite')     || getHashParam('idSite');
            return { category: category, subcategory: subcategory, idSite: idSite };
        }

        // ── DOM polling ───────────────────────────────────────────────────────
        // Check every 500ms. When .reporting-page is present AND
        // category+subcategory are in the URL, ensure the button exists.

        setInterval(function () {
            var $page = $('.reporting-page');

            if (!$page.length) {
                // Not a report page — remove button bar if it exists
                if ($('#reportSorterToggle').length) {
                    $('#reportSorterBar').remove();
                    state.pageKey = null;
                }
                return;
            }

            var p       = readCurrentPage();
            var pageKey = p.category + '|' + p.subcategory;

            if (!p.category || !p.subcategory || !p.idSite) {
                return; // Params not ready yet
            }

            // Update state
            state.category    = p.category;
            state.subcategory = p.subcategory;
            state.idSite      = p.idSite;

            // Add button if not already present for this page
            if ($('#reportSorterToggle').length && state.pageKey === pageKey) {
                return; // Already there for this page
            }

            state.pageKey = pageKey;
            $('#reportSorterBar').remove();
            addButton();

        }, 500);

        // ── Sort button ───────────────────────────────────────────────────────

        function addButton() {
            var $btn = $('<button>', {
                id:    'reportSorterToggle',
                'class': 'btn',
                title: 'Sort reports on this page'
            })
            .html('&#8597;&nbsp;Sort reports')
            .on('click', openModal);

            $('<div>', { id: 'reportSorterBar' })
                .append($btn)
                .prependTo('.reporting-page');
        }

        // ── Modal ─────────────────────────────────────────────────────────────

        function openModal() {
            // Read widgets directly from the DOM — no API call needed, no auth issues.
            // Each widget rendered by Matomo's Vue layer is a .matomo-widget element
            // whose id attribute is the widget's uniqueId (e.g. widgetUserCountrygetCountry).
            var widgets = [];

            // Only top-level widgets — skip children nested inside another .matomo-widget
            // (e.g. widgetContinent is a container; widgetUserCountrygetContinent is its child).
            $('.reporting-page .matomo-widget[id]').filter(function () {
                return $(this).parents('.matomo-widget').length === 0;
            }).each(function () {
                var uniqueId = this.id;
                if (!uniqueId) { return; }

                // The EnrichedHeadline Vue component renders the report name inside
                // .enrichedHeadline .title — targeting that avoids grabbing the hidden
                // description and "Report generated" timestamp from .inlineHelp.
                var $title = $(this).find('.enrichedHeadline .title').first();
                if (!$title.length) {
                    // Fallback for widgets that don't use EnrichedHeadline
                    $title = $(this).find('h2').first();
                }
                var name = $title.text().trim() || uniqueId;

                widgets.push({ id: uniqueId, name: name });
            });

            if (widgets.length < 2) {
                alert('This page has fewer than 2 sortable reports — nothing to reorder.');
                return;
            }

            showModal(widgets);
        }

        function showModal(widgets) {
            var listHtml = widgets.map(function (w) {
                return '<li class="rsItem" data-id="' + esc(w.id) + '">' +
                    '<span class="rsHandle" title="Drag to reorder">&#9783;</span>' +
                    '<span class="rsName">' + esc(w.name) + '</span>' +
                    '</li>';
            }).join('');

            var $overlay = $('<div>', { id: 'reportSorterOverlay' });
            var $modal   = $('<div>', {
                id:          'reportSorterModal',
                role:        'dialog',
                'aria-modal': 'true',
                'aria-label': 'Sort reports'
            });

            $modal.html(
                '<div id="rsHeader">' +
                    '<h2>Sort Reports</h2>' +
                    '<button id="rsClose" aria-label="Close">&#10005;</button>' +
                '</div>' +
                '<p id="rsHint">Drag reports to reorder them. Your order is saved per user.</p>' +
                '<ul id="rsList">' + listHtml + '</ul>' +
                '<div id="rsFooter">' +
                    '<button id="rsReset" class="btn btn-outline">Reset to default</button>' +
                    '<button id="rsSave" class="btn">Save order</button>' +
                '</div>'
            );

            $overlay.append($modal).appendTo('body');
            makeSortable(document.getElementById('rsList'));

            // Resolve auth token once — shared by save and reset handlers
            var token = (window.Matomo && window.Matomo.token_auth)
                ? window.Matomo.token_auth
                : (window.piwik && window.piwik.token_auth ? window.piwik.token_auth : '');

            // Close
            $('#rsClose').on('click', function () { $overlay.remove(); });
            $overlay.on('click', function (e) {
                if (e.target === $overlay[0]) $overlay.remove();
            });
            $(document).on('keydown.reportSorter', function (e) {
                if (e.key === 'Escape') {
                    $overlay.remove();
                    $(document).off('keydown.reportSorter');
                }
            });

            // Save
            $('#rsSave').on('click', function () {
                var orderedIds = [];
                $('#rsList .rsItem').each(function () {
                    orderedIds.push($(this).data('id'));
                });
                var $btn = $(this).prop('disabled', true).text('Saving\u2026');
                // Use the Controller action (session auth via force_api_session).
                // force_api_session=1 makes makeSessionAuthenticator() use the session
                // rather than treating token_auth as a standalone API token.
                $.ajax({
                    url:    'index.php',
                    method: 'POST',
                    data: {
                        module:            'ReportSorter',
                        action:            'saveOrder',
                        categoryId:        state.category,
                        subcategoryId:     state.subcategory,
                        reportIds:         JSON.stringify(orderedIds),
                        token_auth:        token,
                        force_api_session: 1
                    },
                    success: function (response) {
                        if (response && response.result === 'error') {
                            $btn.prop('disabled', false).text('Save order');
                            alert('Save failed: ' + (response.message || JSON.stringify(response)));
                            return;
                        }
                        $overlay.remove();
                        window.location.reload();
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false).text('Save order');
                        alert('Could not save order. HTTP ' + xhr.status + '\n' + xhr.responseText.substring(0, 300));
                    }
                });
            });

            // Reset
            $('#rsReset').on('click', function () {
                if (!confirm('Reset to default report order for this page?')) return;
                var $btn = $(this).prop('disabled', true).text('Resetting\u2026');
                $.ajax({
                    url:    'index.php',
                    method: 'POST',
                    data: {
                        module:            'ReportSorter',
                        action:            'resetOrder',
                        categoryId:        state.category,
                        subcategoryId:     state.subcategory,
                        token_auth:        token,
                        force_api_session: 1
                    },
                    success: function () {
                        $overlay.remove();
                        window.location.reload();
                    },
                    error: function () {
                        $btn.prop('disabled', false).text('Reset to default');
                        alert('Could not reset order. Please try again.');
                    }
                });
            });
        }

        // ── HTML5 drag-and-drop sortable list ─────────────────────────────────

        function makeSortable(list) {
            var dragSrc = null;

            function getItemAfter(container, y) {
                var items = [].slice.call(container.querySelectorAll('.rsItem:not(.rsDragging)'));
                return items.reduce(function (closest, child) {
                    var box    = child.getBoundingClientRect();
                    var offset = y - box.top - box.height / 2;
                    if (offset < 0 && offset > closest.offset) {
                        return { offset: offset, element: child };
                    }
                    return closest;
                }, { offset: Number.NEGATIVE_INFINITY }).element;
            }

            [].forEach.call(list.querySelectorAll('.rsItem'), function (item) {
                item.setAttribute('draggable', 'true');

                item.addEventListener('dragstart', function (e) {
                    dragSrc = this;
                    this.classList.add('rsDragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', this.dataset.id);
                });

                item.addEventListener('dragend', function () {
                    this.classList.remove('rsDragging');
                });
            });

            list.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (!dragSrc) return;
                var after = getItemAfter(list, e.clientY);
                if (!after) {
                    list.appendChild(dragSrc);
                } else if (after !== dragSrc) {
                    list.insertBefore(dragSrc, after);
                }
            });

            list.addEventListener('drop', function (e) { e.preventDefault(); });
        }

        // ── Helpers ───────────────────────────────────────────────────────────

        function esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

    } // end bootReportSorter

}());
