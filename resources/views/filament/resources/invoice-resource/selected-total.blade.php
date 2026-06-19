<span
    x-data="{
        total: '',
        isTotalLoading: false,
        refreshTimer: null,
        refreshRequest: 0,
        queueRefresh(state) {
            window.clearTimeout(this.refreshTimer)

            const request = ++this.refreshRequest

            if (! state.count) {
                this.total = ''
                this.isTotalLoading = false

                return
            }

            this.isTotalLoading = true
            this.refreshTimer = window.setTimeout(() => this.refreshTotal(state, request), 150)
        },
        async refreshTotal(state, request) {
            const total = await $wire.call(
                'getSelectedInvoicesTotal',
                state.selected,
                state.deselected,
                state.tracking,
            )

            if (request !== this.refreshRequest) {
                return
            }

            this.total = total
            this.isTotalLoading = false
        },
    }"
    x-init="
        $watch(
            () => JSON.stringify({
                count: getSelectedRecordsCount(),
                tracking: isTrackingDeselectedRecords,
                selected: [...selectedRecords],
                deselected: [...deselectedRecords],
            }),
            (state) => queueRefresh(JSON.parse(state)),
        )
    "
    class="text-sm font-medium text-gray-700 dark:text-gray-200"
>
    Выбрано счетов на сумму:
    <span x-show="isTotalLoading">…</span>
    <span x-show="! isTotalLoading" x-text="total"></span>
</span>
