setActiveNavTab("/wydarzenia");

const $eventsHeading = $("#events-heading");
const $failModal = $("#fail-modal");
const $synchronizingMessage = $("#synchronizing-message");
const $dayMarkingsStyles = $("#day-markings");
const $noEventsMessage = $("#no-events-message");
const $eventList = $(".event-list");

const dayMarkingsStylesTemplate = $dayMarkingsStyles.data("template").replaceAll("\n", "");
const eventListItemTemplate = $eventList.find("li").get(0).outerHTML;

function renderEventListItemTemplate(eventData) {
    let rendered = eventListItemTemplate;
    
    for (let [ name, value ] of Object.entries(eventData)) {
        if (name === "at") {
            const momentValue = moment(value);
            value = momentValue.format("dddd [o] H:mm");
            value += ` &bullet; (${momentValue.fromNow()})`;
        }
        
        rendered = rendered.replaceAll(`%${name}%`, value);
    }
    
    return rendered;
}

function renderEvents(eventList) {
    $eventList.html("");
    
    for (const event of eventList) {
        const html = renderEventListItemTemplate(event);
        $eventList.append(html);
    }
}

let events = null;

function generateDayMarkings() {
    let selectors = [];
    
    for (const date of Object.keys(events)) {
        selectors.push(`.day[data-date="${date}"] span::before`);
    }
    
    let countersCss = "";
    
    for (const [ date, eventList ] of Object.entries(events)) {
        countersCss += `.day[data-date="${date}"] span::before { content: "${eventList.length}" } `;
    }
    
    const css = `${selectors.join()}{${dayMarkingsStylesTemplate}}${countersCss}`;
    
    $dayMarkingsStyles.text(css);
}

function onDateSelected(momentDate, rawDate) {
    const formatted = momentDate.format("dddd, D MMMM YYYY");
    $eventsHeading.text(formatted);
    
    if (events && rawDate in events) {
        $noEventsMessage.css({
            display: "none",
            animation: null
        });
        renderEvents(events[rawDate]);
        $eventList.css("display", "block");
    } else {
        $eventList.css("display", "none");
        $noEventsMessage.css({
            display: "block",
            animation: "slide-right 250ms forwards"
        });
    }
}

function onEventsUpdate() {
    $synchronizingMessage.attr("data-animatedshow", "0");
    
    if (!events) return;
    
    generateDayMarkings(Object.keys(events));
}

function updateEvents() {
    $synchronizingMessage.attr("data-animatedshow", "1");
    fetch("/wydarzenia/json")
        .then(response => {
            if (response.ok) {
                return response.json();
            } else {
                throw new Error();
            }
        })
        .then(data => {
            events = data;
            onEventsUpdate();
        })
        .catch(() => {
            onEventsUpdate();
            $failModal.modal("show");
        })
        .finally(() => {
            const currentMomentDate = moment(currentDate);
            onDateSelected(currentMomentDate, currentMomentDate.format("YYYYMMDD"));
        });
}

settings.onClickDate = date => {
    if (!date) return;
    
    const rawDate = String(date);
    const momentDate = moment(rawDate);
    const nativeDate = momentDate.toDate();
    
    if (nativeDate.toString() === currentDate.toString()) return;
    
    onDateSelected(momentDate, rawDate);
    selectDate(nativeDate);
}

updateEvents();