setActiveNavTab("/wydarzenia");

const $eventsHeading = $("#events-heading");
const $failModal = $("#fail-modal");
const $synchronizingMessage = $("#synchronizing-message");
const $dayMarkingsStyles = $("#day-markings");
const $newEventIcon = $("#new-event-icon");
const $newEventIconPicker = $("#new-event-icon-picker");
const $newEventTitleInput = $("#new-event-title-input");
const $newEventAtInput = $("#new-event-at-input");
const $newEventDescriptionInput = $("#new-event-description-input");
const $eventViewModal = $("#event-view-modal");
const $eventViewIcon = $("#event-view-icon");
const $eventViewTitle = $("#event-view-title");
const $eventViewAt = $("#event-view-at");
const $eventViewDescription = $("#event-view-description");
const $eventViewPartakingUsersList = $("#event-view-partaking-users-list");
const $deleteEventBtn = $("#delete-event-btn");

class EventListComponent extends StatefulComponent {
    render() {
        /** @type {PlackiEvent[]} */
        const events = this.state;

        if (events.length === 0) {
            return `
                <div id="no-events-message" style="position: relative; animation: slide-right 250ms forwards">
                    <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="1em"
                            height="1em"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            fill="none"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="icon icon-tabler icon-tabler-bell-off"
                            style="font-size: 22px; position: relative; top: -4px; color: var(--bs-gray-600);"
                    >
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <line x1="3" y1="3" x2="21" y2="21"></line>
                        <path d="M17 17h-13a4 4 0 0 0 2 -3v-3a7 7 0 0 1 1.279 -3.716m2.072 -1.934c.209 -.127 .425 -.244 .649 -.35a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3"></path>
                        <path d="M9 17v1a3 3 0 0 0 6 0v-1"></path>
                    </svg>
                    <h4 class="d-inline-block" style="font-family: 'DM Sans', sans-serif; font-weight: bold; color: var(--bs-gray-600);">
                        brak wydarzeń w tym dniu,&nbsp;<a href="#" data-bs-target="#event-creation-modal" data-bs-toggle="modal">możesz dodać pierwsze!</a>
                    </h4>
                </div>
            `;
        } else {
            return [
                `<ul class="list-unstyled event-list">`,
                ...events.map(event => `
                    <li
                            class="d-flex"
                            data-eventid="${event.id}"
                            data-json="${Base64.encode(JSON.stringify(event))}"
                            onclick="viewEvent(JSON.parse(Base64.decode(this.getAttribute('data-json'))));"
                    >
                        <div class="d-flex flex-grow-0 align-items-center">
                            <img src="/cdn/event_icons/${event.id}" />
                        </div>
                        <div class="flex-grow-1">
                            <p>${escapeHtml(event.title)}</p>
                            <p>${escapeHtml(event.description)}</p>
                        </div>
                        <div class="d-flex flex-grow-0 align-items-center">
                            <label class="form-label">${event.at}</label>
                        </div>
                    </li>
                `),
                `
                    <li class="d-flex" data-bs-target="#event-creation-modal" data-bs-toggle="modal">
                        <div class="d-flex flex-grow-0 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-plus" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                               <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                               <rect x="4" y="5" width="16" height="16" rx="2"></rect>
                               <line x1="16" y1="3" x2="16" y2="7"></line>
                               <line x1="8" y1="3" x2="8" y2="7"></line>
                               <line x1="4" y1="11" x2="20" y2="11"></line>
                               <line x1="10" y1="16" x2="14" y2="16"></line>
                               <line x1="12" y1="14" x2="12" y2="18"></line>
                            </svg>
                        </div> 
                        <div class="flex-grow-1">
                            <p>dodaj</p>
                            <p>stwórz nowe wydarzenie i dodaj je do kalendarze</p>
                        </div>
                    </li>
                </ul>
                `
            ];
        }
    }
}

const eventListComponent = new EventListComponent("event-list-placeholder", {}, []);

const dayMarkingsStylesTemplate = $dayMarkingsStyles.data("template").replaceAll("\n", "");

const params = new URLSearchParams(location.search);
const initDate = params.get("date");

/**
 * @typedef {Object} PlackiEvent
 * @property {number} id
 * @property {string} title
 * @property {string} description
 * @property {string} at
 * @property {string} organiser
 * @property {{pic: string, username: string}[]} partaking
 * @property {boolean} selfIsPartaking
 * @property {boolean} selfIsOrganiser
 */

/** @type {PlackiEvent} */
let currentViewEvent;
let syncedOnce = false;

/**
 * @param eventList {PlackiEvent[]}
 */
function renderEvents(eventList) {
    eventListComponent.state = eventList;
}

/** @type {?Object<string, PlackiEvent[]>} */
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

/**
 * @param momentDate {{format(string): string}}
 * @param rawDate {string}
 */
function onDateSelected(momentDate, rawDate) {
    const formatted = momentDate.format("dddd, D MMMM YYYY");
    $eventsHeading.text(formatted);
    renderEvents(events[rawDate] ?? []);
    $newEventAtInput.val(momentDate.format("YYYY-MM-DD[T12:00]"))
}

/**
 * @param cache {boolean} specifies whether the <code>events</code> variable should be cached to <code>localStorage</code>
 */
function onEventsUpdate(cache = true) {
    $synchronizingMessage.attr("data-animatedshow", "0");
    
    if (!events) return;

    if (cache) {
        localStorage.setItem("cachedEvents", JSON.stringify(events));
        localStorage.setItem("lastEventsCachingTime", new Date().toString());
    }

    generateDayMarkings(Object.keys(events));

    for (const eventList of Object.values(events)) {
        for (const event of eventList) {
            const momentDate = moment(event.at)
            event.at = `${momentDate.format("dddd [o] H:mm")} &bullet; ${momentDate.fromNow()}`;
        }
    }
}

async function updateEvents(preferCache = false) {
    const selectCurrentDate = () => {
        const currentMomentDate = moment(currentDate);
        onDateSelected(currentMomentDate, currentMomentDate.format("YYYYMMDD"));
    };

    const cachedEvents = localStorage.getItem("cachedEvents");
    const lastCache = "lastEventsCachingTime" in localStorage ?
        new Date(localStorage.getItem("lastEventsCachingTime"))
        :
        new Date();

    if (preferCache && cachedEvents && new Date() - lastCache < 86400000) {  // checks if cache is preffered, cache exists and cache is newer than 24 hours
        events = JSON.parse(cachedEvents);
        onEventsUpdate(false);
        selectCurrentDate();
    } else {
        $synchronizingMessage.attr("data-animatedshow", "1");

        try {
            const response = await fetch("/wydarzenia/json");

            if (!response.ok) {
                throw new Error();
            }

            events = await response.json();
            syncedOnce = true;
            onEventsUpdate();
        } catch (e) {
            onEventsUpdate();
            $failModal.modal("show");
        } finally {
            selectCurrentDate();
        }
    }
}

settings.onClickDate = date => {
    if (!date) return;
    
    const rawDate = String(date);
    const momentDate = moment(rawDate);
    const nativeDate = momentDate.toDate();
    
    if (nativeDate.toString() === currentDate.toString()) return;
    
    onDateSelected(momentDate, rawDate);
    // noinspection JSUnresolvedFunction
    selectDate(nativeDate);  // this is a function coming from the calendar script, however it is not detected properly by JetBrains, so this inspection is turned off here
}

updateEvents(!params.has("forceSync"))
    .then(() => {
        if (initDate) {
            settings.onClickDate(initDate);
        }
    });

$newEventIconPicker.on("change", () => {
    const chosenIconFile = $newEventIconPicker.prop("files")[0];

    $newEventIcon.attr("src", URL.createObjectURL(chosenIconFile));
});

async function createEventAction() {
    const formData = new FormData();

    const chosenIconFile = $newEventIconPicker.prop("files")[0];

    if (!chosenIconFile) {
        Toast.show("wybierz ikonę wydarzenia", "alert", 1);
        return;
    }

    formData.append("icon", chosenIconFile);

    const title = $newEventTitleInput.val().trim();

    if (!title) {
        Toast.show("podaj tytuł wydarzenia", "alert", 1);
        return;
    }

    formData.append("title", title);

    const at = $newEventAtInput.val();

    if (!at) {
        Toast.show("podaj datę wydarzenia", "alert", 1);
        return;
    }

    formData.append("at", String(at));

    const description = $newEventDescriptionInput.val().trim();

    if (description) {
        formData.append("description", description);
    }

    const response = await fetch(location.href, {
        method: "POST",
        body: formData
    });

    if (response.ok) {
        Toast.show("dodano wydarzenie", "calendar_add");
        location.search = new URLSearchParams({
            "date": moment(at).format("YYYYMMDD"),
            "forceSync": "1"
        }).toString();
    } else {
        Toast.show("nie udało się dodać wydarzenia", "alert", 2);
    }
}

async function deleteViewedEvent() {
    Toast.show("usuwanie wydarzenia...", "bin");

    try {
        const response = await fetch(`/wydarzenia?id=${currentViewEvent.id}`, {
            method: "DELETE"
        });

        if (!response.ok) throw new Error();

        Toast.show("usunięto wydarzenie", "bin", 1);
        $eventViewModal.modal("hide");
        await updateEvents();
    } catch (e) {
        Toast.show("nie udało się usunąć wydarzenia", "alert", 2);
    }
}

/**
 * @param data {PlackiEvent}
 */
function viewEvent(data) {
    /*
    if information was loaded from cache instead of the remote server, synchronize the calendar and re-open the event
    modal with new information loaded.
     */
    if (!syncedOnce) {
        Toast.show("aktualizowanie informacji...", "hourglass");
        updateEvents().then(() => {
            Toast.dismiss();
            const eventElement = $(`.event-list > li[data-eventid=${data.id}]`).get(0);

            if (eventElement instanceof HTMLLIElement) {
                eventElement.click();
            } else {
                Toast.show("wydarzenie już nie istnieje", "alert", 2);
            }
        });
        return;
    }

    currentViewEvent = data;

    $eventViewIcon.attr("src", `/cdn/event_icons/${currentViewEvent.id}`);
    $eventViewTitle.text(currentViewEvent.title);
    $eventViewAt.html(currentViewEvent.at);

    if (currentViewEvent.description) {
        const description = DOMPurify.sanitize(
            currentViewEvent.description.replaceAll("\n", "<br/>")
        );

        $eventViewDescription.html(description);
    } else {
        $eventViewDescription.html("<b>brak opisu</b>");
    }

    BigChooser.setActiveOption("event-partake", currentViewEvent.selfIsPartaking ? "yes" : "no");
    $eventViewPartakingUsersList.html("");

    for (const user of currentViewEvent.partaking) {
        $eventViewPartakingUsersList.append(`
            <div class="user-list-item">
                <img height="30" width="30" src="${user.pic}" />
                <label class="form-label">
                    ${user.username}
                    ${(user.username === currentViewEvent.organiser ? "<i style='color: var(--bs-gray-600);'>(organizator)</i>" : "")}
                </label>
            </div>
        `);
    }

    $deleteEventBtn.css("display", currentViewEvent.selfIsOrganiser ? "block" : "none");
    $eventViewModal.modal("show");
}

BigChooser.setOnSwitch("event-partake", async optionId => {
    try {
        const formData = new FormData();
        formData.append("partake", optionId);
        
        const response = await fetch(`/wydarzenia/udzial?id=${currentViewEvent.id}`, {
            method: "POST",
            body: formData
        });

        if (!response.ok) {
            if (response.status === 400 && await response.text() === "is organiser") {
                Toast.show("nie możesz zmienić udziału jako organizator", "info", 3);
                BigChooser.setActiveOption("event-partake", "yes");
            } else {
                throw new Error();
            }
        }
    } catch (e) {
        Toast.show("nie udało się zgłosić udziału", "alert", 2);
        BigChooser.setActiveOption(
            "event-partake",
            optionId === "yes" ? "no" : "yes"
        );
    }
});