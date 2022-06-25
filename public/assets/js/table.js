class SearchTables {
    static load() {
        $(".search").on("keypress", function(ev) {
            if (ev.key === "Enter") {
                const self = $(this);

                self.trigger("blur");
                const targetTable = self.data("target-table");
                const rowsToSearch = $(`.table[data-tablename="${targetTable}"] > table > tbody > tr:not(.warning.no-result)`);
                const warning = $(`.table[data-tablename="${targetTable}"] .warning.no-result`);
                const searchTokens = self.val().split(" ").filter(item => item.length > 0);

                warning.css("display", "none");

                if (searchTokens.length === 0) {
                    $(`.table[data-tablename="${targetTable}"] tr:not(.warning.no-result)`).css("display", "table-row");
                    return;
                }

                let resultsNumber = 0;

                for (const row of rowsToSearch) {
                    const cell = $(row).find("td").get(0);
                    const cellText = cell.textContent.toLowerCase();
                    let display = "none";

                    for (const searchToken of searchTokens) {
                        if (cellText.includes(searchToken.toLowerCase())) {
                            display = null;
                            resultsNumber++;
                            break;
                        }
                    }

                    row.style.display = display;
                }

                if (resultsNumber === 0) {
                    warning.css("display", "table-row")
                }
            }
        });

        $(".press-enter-to-search-message[data-depends]").each((index, el) => {
            const $el = $(el);
            const depends = $el.data("depends");

            $el.attr("data-animationenabled", "0");

            if (depends) {
                const dependencies = $(depends);

                dependencies.on("focus", () => {
                    $el.attr("data-animationenabled", "1");
                    $el.attr("data-animatedshow", "1");
                });

                dependencies.on("blur", () => {
                    $el.attr("data-animatedshow", "0");
                });
            }
        });
    }
}

class SearchTableComponent extends StatefulComponent {
    constructor(placeholderId, props) {
        super(placeholderId, props, null);
        this.postRender = () => {
            $(`.search[data-target-table="${this.props.tableName}"]`).on("keypress", ev => {
                if (ev.key === "Enter") {
                    this.modifyState(currentState => {
                        return {
                            ...currentState,
                            searchQuery: ev.target.value
                        }
                    });
                }
            });

            $(".press-enter-to-search-message[data-depends]").each((index, el) => {
                const $el = $(el);
                const depends = $el.data("depends");

                if (depends) {
                    const dependencies = $(depends);

                    dependencies.on("focus", () => {
                        $el.attr("data-animatedshow", "1");
                    });

                    dependencies.on("blur", () => {
                        $el.attr("data-animatedshow", "0");
                    });
                }
            });
        };
    }

    render() {
        /** @type {?{data: Object[], searchQuery: ?string}} */
        const tableData = this.state;

        if (!tableData) {
            return `<h1 style="font-family: 'Josefin Sans', sans-serif;">${this.props.loadingText}</h1>`;
        }

        let rows = tableData.data;

        if (tableData.searchQuery) {
            const tokenizedSearchQuery = tableData.searchQuery.split(" ").filter(item => item.length > 0);

            rows = rows.filter(item => this.props.searchEngine(tokenizedSearchQuery, item));
        }

        return [`
            <div style="width: 100%;">
                <div class="form-group pull-right col-lg-4" style="margin-top: 10px; margin-bottom: 10px; margin-right: 10px;">
                    <input
                        type="text"
                        class="search form-control"
                        placeholder="${this.props.searchPlaceholder ?? 'wyszukaj'}"
                        data-target-table="${this.props.tableName}"
                        value="${tableData.searchQuery ?? ''}"
                    />
                    <span class="press-enter-to-search-message" data-depends='.search[data-target-table="${this.props.tableName}"]' data-animatedshow="0">
                        wciśnij&nbsp;<img src="/assets/img/enter%20key.png" />&nbsp;<span class="font-monospace">Enter</span> aby wyszukać
                    </span>
                </div>
            </div>
            <div class="table-responsive table table-hover results" data-tablename="${this.props.tableName}">
                <table class="table table-hover table-borderless" style="border: none; vertical-align: middle;">
                    <thead class="bill-header cs">
                        <tr>
            `,
            ...this.props.rows.map(rowDetails => `
                            <th style="width: ${rowDetails.width}">${rowDetails.title}</th>
            `),
            `
                        </tr>
                    </thead>
                    <tbody>
            `,
            rows.length === 0 ? `
                        <tr class="warning no-result" style="display: table-row;">
                            <td colspan="12" style="font-family: 'Josefin Sans', sans-serif;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-triangle" width="1.5em" height="1.5em" viewBox="0 0 24 24" stroke-width="2" stroke="#F44336" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M12 9v2m0 4v.01"></path>
                                   <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"></path>
                                </svg>
                                brak wyników
                            </td>
                        </tr>
            ` : "",
            ...rows.map((item, index) => this.props.itemRenderer(item, index)),
            `
                    </tbody>
                </table>
            </div>`
        ];
    }
}