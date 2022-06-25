/**
 * @typedef {Object} PlackiVideo
 * @property {string} id
 * @property {string} name
 * @property {string} thumbnail
 * @property {string} uploadedAt
 * @property {boolean} isPrivate
 */

const $pfpInput = $("#pfp-input");

const myVideosList = new SearchTableComponent("my-videos-list", {
    loadingText: "ładowanie filmów...",
    searchPlaceholder: "wyszukaj filmy",
    searchEngine: (tokenizedSearchQuery, item) => {
        /** @type {PlackiVideo} */
        const video = item;

        const lowerCaseName = video.name.toLowerCase();
        let matches = false;

        for (const token of tokenizedSearchQuery) {
            if (lowerCaseName.includes(token.toLowerCase())) {
                matches = true;
                break;
            }
        }

        return matches;
    },
    rows: [
        {title: "tytuł", width: "75%"},
        {title: "data wrzucenia", width: "25%"}
    ],
    itemRenderer: item => {
        /** @type {PlackiVideo} */
        const video = item;

        return `
            <tr>
                <td>
                    <a href="/media/film?id=${video.id}">
                        <img class="video-thumbnail" src="${video.thumbnail}"/>
                        ${video.isPrivate ?
                            `
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock" width="1em" height="1em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <circle cx="12" cy="16" r="1"></circle>
                                    <path d="M8 11v-4a4 4 0 0 1 8 0v4"></path>
                                </svg>
                            ` : ``}
                        ${escapeHtml(video.name)}
                    </a>
                </td>
                <td>${video.uploadedAt}</td>
            </tr>
        `;
    },
    tableName: "videos-table"
});
myVideosList.reRender();

/**
 * @typedef {Object} PlackiPhoto
 * @property {string} id
 * @property {string} album
 */

class PhotoListComponent extends StatefulComponent {
    render() {
        /** @type {?{data: PlackiPhoto[], filterAlbum: ?string}} */
        const photos = this.state;

        if (!photos) {
            return `<h1 style="font-family: 'Josefin Sans', sans-serif;">ładowanie zdjęć...</h1>`;
        }

        if (photos.filterAlbum) {
            photos.data = photos.data.filter(photo => photo.album === photos.filterAlbum);
        }

        return photos.data.map((photo, index) => `
            <div class="media-photo-item">
                <div
                        style='background: url("/media_sources/${photo.id}") center / cover no-repeat;'
                        onclick="open('/media_sources/${photo.id}', '_blank', 'location=no,status=no');"
                ></div>
                <div style="padding: 3px;">
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-album" width="1em" height="1em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                            <path d="M12 4v7l2 -2l2 2v-7"></path>
                        </svg>
                        <strong>${escapeHtml(photo.album)}</strong>
                    </p>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-danger" onclick="deletePhoto('${photo.id}', ${index})">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="13" height="13" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <line x1="4" y1="7" x2="20" y2="7"></line>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                            </svg>
                            usuń
                        </button>
                    </div>
                </div>
            </div>
        `);
    }
}

const myPhotosList = new PhotoListComponent("my-photos-list");
myPhotosList.reRender();

/**
 * @typedef {Object} PlackiFile
 * @property {string} id
 * @property {string} name
 * @property {string} size
 * @property {string} uploadedAt
 * @property {boolean} isShared
 */

const myFileList = new SearchTableComponent("my-file-list", {
    loadingText: "ładowanie plików...",
    searchPlaceholder: "wyszukaj pliki",
    searchEngine: (tokenizedSearchQuery, item) => {
        /** @type {PlackiFile} */
        const file = item;

        const lowerCaseName = file.name.toLowerCase();
        let matches = false;

        for (const token of tokenizedSearchQuery) {
            if (lowerCaseName.includes(token.toLowerCase())) {
                matches = true;
                break;
            }
        }

        return matches;
    },
    rows: [
        {title: "nazwa pliku", width: "auto"},
        {title: "data wrzucenia", width: "220px"},
        {title: "akcje", width: "280px"}
    ],
    itemRenderer: (item, index) => {
        /** @type {PlackiFile} */
        const file = item;

        return `
            <tr>
                <td>
                    <div class="file-icon" data-file="${escapeHtml(file.name.split('.', 2).pop())}"></div>
                    ${escapeHtml(file.name)}
                    <span class="float-end" style="color: var(--bs-gray-500);">${file.size}</span>
                </td>
                <td>${file.uploadedAt}</td>
                <td>
                    <div class="btn-group btn-group-sm float-end file-actions" role="group">
                        <button class="btn btn-danger float-end" type="button" onclick="deleteFile('${file.id}', ${index});">
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
                                    class="icon icon-tabler icon-tabler-trash"
                            >
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <line x1="4" y1="7" x2="20" y2="7"></line>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                            </svg>
                            &nbsp;usuń
                        </button>
                        <button class="btn btn-primary" type="button" onclick="downloadFile('${file.id}', '${file.name}');">
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
                                    class="icon icon-tabler icon-tabler-download"
                            >
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                <polyline points="7 11 12 16 17 11"></polyline>
                                <line x1="12" y1="4" x2="12" y2="16"></line>
                            </svg>
                            &nbsp;pobierz
                        </button>
                        ${file.isShared ?
                            `
                                <button class="btn btn-success" type="button" onclick="shareFile('${file.id}', ${index})">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-link" width="1em" height="1em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5"></path>
                                        <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5"></path>
                                    </svg>
                                    &nbsp;kopiuj link
                                </button>
                            `
                            :
                            `
                                <button class="btn btn-light" type="button" onclick="shareFile('${file.id}', ${index})">
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
                                            class="icon icon-tabler icon-tabler-share"
                                    >
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <circle cx="6" cy="12" r="3"></circle>
                                        <circle cx="18" cy="6" r="3"></circle>
                                        <circle cx="18" cy="18" r="3"></circle>
                                        <line x1="8.7" y1="10.7" x2="15.3" y2="7.3"></line>
                                        <line x1="8.7" y1="13.3" x2="15.3" y2="16.7"></line>
                                    </svg>
                                    &nbsp;udostępnij
                                </button>
                            `
                        }
                    </div>
                </td>
            </tr>
        `
    },
    tableName: "files-table"
});
myFileList.reRender();

/**
 * @typedef {Object} LikedPost
 * @property {number} id
 * @property {string} author author's username
 * @property {string} content
 * @property {string} at formatted date of creation
 */

const likedPostsList = new SearchTableComponent("liked-posts-list", {
    loadingText: "ładowanie postów...",
    searchPlaceholder: "szukaj w polubionych postach",
    searchEngine: (tokenizedSearchQuery, item) => {
        /** @type {LikedPost} */
        const post = item;

        const lowerCaseContent = post.content.toLowerCase();
        let matches = false;

        for (const token of tokenizedSearchQuery) {
            if (lowerCaseContent.includes(token.toLowerCase())) {
                matches = true;
                break;
            }
        }

        return matches;
    },
    rows: [
        {title: "id", width: "70px"},
        {title: "autor", width: "160px"},
        {title: "zawartość", width: "auto"},
        {title: "data wstawienia", width: "250px"}
    ],
    itemRenderer: (item, index) => {
        /** @type {LikedPost} */
        const post = item;

        return `
            <tr>
                <td>
                    <a href="#">#${post.id}</a>
                </td>
                <td>${post.author}</td>
                <td style="color: gray;">${post.content ?? "<b>brak treści</b>"}</td>
                <td>${post.at}</td>
            </tr>
        `;
    },
    tableName: "liked-posts-table"
});
likedPostsList.reRender();

/**
 * @typedef {Object} LikedVideo
 * @property {string} id
 * @property {string} thumbnail
 * @property {string} name
 * @property {boolean} isPrivate
 */

const likedVideosList = new SearchTableComponent("liked-videos-list", {
    loadingText: "ładowanie filmów...",
    searchPlaceholder: "szukaj w polubionych filmach",
    searchEngine: (tokenizedSearchQuery, item) => {
        /** @type {LikedVideo} */
        const video = item;

        const lowerCaseName = video.name.toLowerCase();
        let matches = false;

        for (const token of tokenizedSearchQuery) {
            if (lowerCaseName.includes(token.toLowerCase())) {
                matches = true;
                break;
            }
        }

        return matches;
    },
    rows: [
        {title: "tytuł", width: "75%"},
    ],
    itemRenderer: item => {
        /** @type {LikedVideo} */
        const video = item;

        return `
            <tr>
                <td>
                    <a href="/media/film?id=${video.id}">
                        <img class="video-thumbnail" src="${video.thumbnail}"/>
                        ${video.isPrivate ?
            `
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock" width="1em" height="1em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <circle cx="12" cy="16" r="1"></circle>
                                    <path d="M8 11v-4a4 4 0 0 1 8 0v4"></path>
                                </svg>
                            ` : ``}
                        ${escapeHtml(video.name)}
                    </a>
                </td>
            </tr>
        `;
    },
    tableName: "liked-videos-table"
});
likedVideosList.reRender();

const visitedMenuOptions = new Set();

setSideMenuOptionCallback(optionId => {
    $(".profile-tab-content").removeClass("d-flex").addClass("d-none");
    $(`#${optionId}-tab`).removeClass("d-none").addClass("d-flex");

    if (!visitedMenuOptions.has(optionId)) {
        onMenuOptionFirstClicked(optionId);
    }

    visitedMenuOptions.add(optionId);
});

/**
 * @param optionId {string}
 */
function onMenuOptionFirstClicked(optionId) {
    switch (optionId) {
        case "my-videos":
            fetch("/ja/moje_media/wideo")
                .then(response => response.json())
                .then(videos => {
                    myVideosList.state = {
                        data: videos,
                        searchQuery: null
                    };
                });
            break;
        case "my-photos":
            fetch("/ja/moje_media/zdjecia")
                .then(response => response.json())
                .then(photos => {
                    myPhotosList.state = {
                        data: photos,
                        filterAlbum: null
                    };
                });
            break;
        case "my-files":
            fetch("/ja/moje_media/pliki")
                .then(response => response.json())
                .then(files => {
                    myFileList.state = {
                        data: files,
                        searchQuery: null
                    };
                });
            break;
        case "liked-posts":
            fetch("/ja/polubione/posty")
                .then(response => response.json())
                .then(likedPosts => {
                    likedPostsList.state = {
                        data: likedPosts,
                        searchQuery: null
                    };
                });
            break;
        case "liked-videos":
            fetch("/ja/polubione/filmy")
                .then(response => response.json())
                .then(likedVideos => {
                    likedVideosList.state = {
                        data: likedVideos,
                        searchQuery: null
                    };
                });
            break;
    }
}

/**
 * @param id {string}
 * @param index {number}
 */
function deletePhoto(id, index) {
    Toast.show("usuwanie zdjęcia...", "bin");

    fetch(`/media/zdjecie?id=${id}`, { method: "DELETE" })
        .then(response => {
            if (!response.ok) throw new Error();

            myPhotosList.modifyState(currentState => {
                /** @type {PlackiPhoto[]} */
                const displayedPhotos = currentState.data;
                displayedPhotos.splice(index, 1);

                return {
                    ...currentState,
                    data: displayedPhotos
                };
            });

            Toast.show("usunięto zdjęcie", "bin", 1);
        })
        .catch(() => {
            Toast.show("nie udało się usunąć zdjęcia", "alert", 2);
        });
}

/**
 * @param id {string}
 * @param index {number}
 */
function deleteFile(id, index) {
    fetch(`/media/plik?id=${id}`, { method: "DELETE" })
        .then(response => {
            if (!response.ok) throw new Error();

            Toast.show("usunięto plik", "bin", 1);
            myFileList.modifyState(currentState => {
                /** @type {PlackiFile[]} */
                const displayedFiles = currentState.data;
                displayedFiles.splice(index, 1);

                return {
                    ...currentState,
                    data: displayedFiles
                };
            });
        })
        .catch(() => {
            Toast.show("nie udało się usunąć pliku", "alert", 2);
        });
}

/**
 * @param id {string}
 * @param filename {string}
 */
function downloadFile(id, filename) {
    Toast.show("rozpoczynanie pobierania...", "download");

    fetch(`/media_sources/${id}`)
        .then(response => response.blob())
        .then(blob => {
            downloadBlob(blob, this.getAttribute("data-filename"));
            Toast.show("pobieranie rozpoczęte", "download", 1);
        });
}

/**
 * @param id {string}
 * @param index {number}
 */
function shareFile(id, index) {
    Toast.show("generowanie linku...", "link");

    fetch(`/media/plik/udostepnij?id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error();

            return response.text();
        })
        .then(token => {
            const url = `${location.origin}/media/plik/udostepnione?token=${token}`;

            myFileList.modifyState(currentState => {
                /** @type {PlackiFile[]} */
                const files = currentState.data;
                files[index].isShared = true;

                return {
                    ...currentState,
                    data: files
                };
            });

            return navigator.clipboard.writeText(url);
        })
        .then(() => {
            Toast.show("skopiowano link do schowka", "link", 2);
        })
        .catch(() => {
            Toast.show("nie udało się udostępnić", "alert", 2);
        });
}

$("#change-pfp-btn").on("click", function() {
    $pfpInput.trigger("click");
});

$pfpInput.on("change", ev => {
    Toast.show("aktualizowanie zdjęcia...", "hourglass");

    /** @type {HTMLInputElement} */
    const input = ev.target;
    const pictureFile = input.files[0];
    const formData = new FormData();

    formData.append("pic", pictureFile);

    fetch("/ja/profilowe", {
        method: "POST",
        body: formData
    })
        .then(response => {
            if (!response.ok) throw new Error();

            location.reload();
        })
        .catch(() => {
            Toast.show("coś poszło nie tak", "alert", 2);
        });
});