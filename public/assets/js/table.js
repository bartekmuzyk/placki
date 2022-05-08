$(document).on("ready", () => {
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
                    console.log(cellText);
                    console.log(searchToken.toLowerCase());
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
});