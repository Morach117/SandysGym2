const LAZY_ROOT_MARGIN = '200px 0px';
const LAZY_THRESHOLD = 0.01;
const MAX_CONCURRENT_INITS = 2;
const INIT_DEBOUNCE_MS = 75;
const FIRST_INIT_DELAY = 50;

var videoPlayers = {};
var vjsObserver = null;
var tabInitTimer = null;

var initQueue = [];
var activeInits = 0;

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return unsafe.toString()
        .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function spinnerHTML() {
    return `
    <div class="text-center text-white py-5">
      <div class="spinner-border text-warning" role="status" style="width:3rem;height:3rem;">
        <span class="sr-only">Cargando...</span>
      </div>
      <p class="mt-3 text-muted">Preparando interfaz...</p>
    </div>`;
}

function enqueueInit(el) {
    return new Promise((resolve, reject) => {
        initQueue.push({ el, resolve, reject });
        drainQueue();
    });
}

function drainQueue() {
    while (activeInits < MAX_CONCURRENT_INITS && initQueue.length > 0) {
        const { el, resolve, reject } = initQueue.shift();
        activeInits++;
        doInit(el)
            .then((player) => { activeInits--; resolve(player); drainQueue(); })
            .catch((err) => { activeInits--; reject(err); drainQueue(); });
    }
}

function doInit(videoEl) {
    return new Promise((resolve, reject) => {
        try {
            if (videoEl.dataset.initialized === '1' || (window.videojs && videojs.players && videojs.players[videoEl.id])) {
                return resolve(videoPlayers[videoEl.id] || null);
            }
            if (!videoEl.dataset.srcLoaded) {
                const src = videoEl.getAttribute('data-src');
                const type = videoEl.getAttribute('data-type') || 'video/mp4';
                if (src) {
                    const source = document.createElement('source');
                    source.src = src;
                    source.type = type;
                    videoEl.appendChild(source);
                    videoEl.dataset.srcLoaded = '1';
                }
            }

            const player = videojs(videoEl, {
                controls: true,
                preload: 'metadata',
                fluid: false,
                controlBar: {
                    children: [
                        'playToggle', 'progressControl', 'currentTimeDisplay',
                        'timeDivider', 'durationDisplay', 'volumePanel', 'fullscreenToggle'
                    ]
                }
            });
            player.on('contextmenu', e => e.preventDefault());

            videoPlayers[videoEl.id] = player;
            videoEl.dataset.initialized = '1';
            resolve(player);
        } catch (err) {
            reject(err);
        }
    });
}

function pauseAllPlayers() {
    Object.values(videoPlayers).forEach(p => {
        if (p && typeof p.pause === 'function') { try { p.pause(); } catch (_) { } }
    });
}

function destroyVideoPlayers() {
    Object.keys(videoPlayers).forEach(id => {
        const p = videoPlayers[id];
        if (p && typeof p.dispose === 'function') {
            try { p.dispose(); } catch (_) { }
        }
    });
    videoPlayers = {};
}

function ensureObserver() {
    if (vjsObserver) return vjsObserver;

    vjsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const el = entry.target;
            if (!entry.isIntersecting) return;

            enqueueInit(el).catch(() => { });

            vjsObserver.unobserve(el);
        });
    }, { rootMargin: LAZY_ROOT_MARGIN, threshold: LAZY_THRESHOLD });

    return vjsObserver;
}

function initializeVideoPlayers(scope) {
    const root = scope || document;
    const obs = ensureObserver();

    root.querySelectorAll('.js-player').forEach((videoEl) => {
        if (!videoEl.id) videoEl.id = 'vjs-' + Math.random().toString(36).slice(2);
        if (videoEl.dataset.initialized === '1' || videoEl.dataset.srcLoaded === '1') return;
        obs.observe(videoEl);
    });
}

function renderVideoPlayer(videoUrl, exerciseName, posterUrl, videoId) {
    if (!videoUrl) return '<p class="text-muted">Video no disponible.</p>';

    const videoBasePath = './assets/videos/';
    const posterBasePath = './assets/img/posters/';
    const fullVideoPath = /^https?:\/\//i.test(videoUrl) ? videoUrl : (videoBasePath + videoUrl);
    const fullPosterPath = posterUrl ? (/^https?:\/\//i.test(posterUrl) ? posterUrl : (posterBasePath + posterUrl)) : '';

    const type = /\.m3u8(\?|$)/i.test(fullVideoPath) ? 'application/x-mpegURL' : 'video/mp4';

    return `
    <div class="video-js-container">
      <video
        id="${escapeHtml(videoId)}"
        class="video-js vjs-default-skin vjs-big-play-centered js-player"
        preload="none"
        poster="${escapeHtml(fullPosterPath)}"
        playsinline
        controls
        controlsList="nodownload"
        style="width:100%; height:280px; border-radius:5px; background:#000;"
        data-src="${escapeHtml(fullVideoPath)}"
        data-type="${type}">
        <p class="vjs-no-js">Tu navegador no soporta videos HTML5.</p>
      </video>
    </div>`;
}

function renderExercises(grupo) {
    if (!grupo || !Array.isArray(grupo.ejercicios) || grupo.ejercicios.length === 0) {
        return '<p class="text-center text-muted py-5">No hay ejercicios definidos para este grupo muscular.</p>';
    }

    return grupo.ejercicios.map((ejercicio, indexEjercicio) => {
        const videoId = `video-${grupo.id_grupo}-${indexEjercicio}`;
        const stats = `
      <div class="exercise-stats">
        <div class="stat-item"><span class="stat-value">${escapeHtml(ejercicio.series || 'N/A')}</span><span class="stat-label">Series</span></div>
        <div class="stat-item"><span class="stat-value">${escapeHtml(ejercicio.repeticiones || 'N/A')}</span><span class="stat-label">Reps</span></div>
        <div class="stat-item"><span class="stat-value">${escapeHtml(ejercicio.descanso || 'N/A')}</span><span class="stat-label">Descanso</span></div>
      </div>`;

        const guide = `
      <div class="exercise-guide">
        ${ejercicio.descripcion ? `<h5><i class="fas fa-info-circle"></i> Descripción</h5><p>${escapeHtml(ejercicio.descripcion)}</p>` : ''}
        ${ejercicio.recomendaciones ? `<h5><i class="fas fa-check-circle"></i> Recomendaciones</h5><p>${escapeHtml(ejercicio.recomendaciones)}</p>` : ''}
      </div>`;

        const idCorrecto = ejercicio.id_ejercicio || ejercicio.id || '';

        const actionBar = `
        <div class="action-bar" style="margin-top: 25px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <button type="button" class="btn-action-pill btn-orange log-progress-btn" 
                    data-ejercicio-id="${escapeHtml(idCorrecto)}" 
                    data-ejercicio-nombre="${escapeHtml(ejercicio.nombre)}">
                <i class="fa-solid fa-fire"></i> Registrar Serie
            </button>
            <button type="button" class="btn-action-pill btn-dark calc-rm-btn">
                <i class="fa-solid fa-calculator"></i> 1RM
            </button>
        </div>`;

        return `
      <div class="exercise-card">
        <div class="row">
          <div class="col-lg-6 mb-4 mb-lg-0">${renderVideoPlayer(ejercicio.video_url, ejercicio.nombre, ejercicio.poster_url, videoId)}</div>
          <div class="col-lg-6">
            <h4>${escapeHtml(ejercicio.nombre)}</h4>
            ${stats}
            ${guide}
            ${actionBar} </div>
        </div>
      </div>`;
    }).join('');
}

function renderRoutineTabs(rutinaPorGrupo, container) {
    if (!rutinaPorGrupo || rutinaPorGrupo.length === 0) {
        container.innerHTML = '<div class="alert alert-info text-center">No se encontraron ejercicios...</div>';
    } else {
        let tabLinksHtml = '<ul class="nav nav-tabs justify-content-center mb-4" id="gruposMuscularesTab" role="tablist">';
        let tabContentHtml = '<div class="tab-content bg-dark p-0 p-md-4 rounded shadow-sm" id="gruposMuscularesTabContent">';

        rutinaPorGrupo.forEach((grupo, indexGrupo) => {
            const grupoId = `grupo-${grupo.id_grupo}`;
            const isActive = indexGrupo === 0;

            tabLinksHtml += `
        <li class="nav-item" role="presentation">
          <a class="nav-link ${isActive ? 'active' : ''}" id="${grupoId}-tab" data-toggle="tab"
             href="#content-${grupoId}" role="tab" aria-controls="content-${grupoId}"
             aria-selected="${isActive ? 'true' : 'false'}">
            ${escapeHtml(grupo.nombre_grupo)}
          </a>
        </li>`;

            tabContentHtml += `
        <div class="tab-pane fade ${isActive ? 'show active' : ''}" id="content-${grupoId}" role="tabpanel" aria-labelledby="${grupoId}-tab">
          ${renderExercises(grupo)}
        </div>`;
        });

        tabLinksHtml += '</ul>';
        tabContentHtml += '</div>';
        container.innerHTML = tabLinksHtml + tabContentHtml;
    }

    setTimeout(() => {
        const activePane = document.querySelector('.tab-pane.active');
        initializeVideoPlayers(activePane || document);
    }, FIRST_INIT_DELAY);

    if (typeof $ === 'function') {
        $('#gruposMuscularesTab a[data-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', function (e) {
            const targetSelector = $(e.target).attr('href');
            const targetPane = document.querySelector(targetSelector);

            pauseAllPlayers();

            clearTimeout(tabInitTimer);
            tabInitTimer = setTimeout(() => {
                initializeVideoPlayers(targetPane);
            }, INIT_DEBOUNCE_MS);
        });

        $('#gruposMuscularesTab a[data-toggle="tab"]').off('click').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    }
}

function loadRoutine(level, gender) {
    const routineContainer = document.getElementById('routineContainer');
    const routineTitle = document.getElementById('routineTitle');

    if (!routineContainer || !routineTitle) {
        console.error('Faltan contenedores en el DOM.');
        return;
    }

    routineContainer.innerHTML = spinnerHTML();
    routineTitle.textContent = 'Cargando Rutina...';

    destroyVideoPlayers();
    initQueue = [];
    activeInits = 0;

    const apiUrl = `./api/get_routine.php?level=${encodeURIComponent(level)}&gender=${encodeURIComponent(gender)}`;

    fetch(apiUrl, { headers: { 'Accept': 'application/json' } })
        .then(async (response) => {
            let data = null;
            try { data = await response.json(); } catch (_) { }
            if (!response.ok) {
                const msg = (data && data.message) ? data.message : `Error HTTP ${response.status}`;
                throw new Error(msg + (data && data.membership_inactive ? ' [membership_inactive]' : ''));
            }
            return data;
        })
        .then((data) => {
            const loaderState = document.getElementById('loaderState');
            if (loaderState) loaderState.style.display = 'none';

            if (data && data.success && Array.isArray(data.rutinaPorGrupo)) {
                routineTitle.textContent = `${data.nivel} - ${data.genero}`;
                renderRoutineTabs(data.rutinaPorGrupo, routineContainer);
            } else {
                const msg = (data && data.message) ? data.message : 'No se pudo cargar la rutina.';
                throw new Error(msg);
            }
        })
        .catch((error) => {
            console.error('Fallo al cargar rutina:', error);
            routineTitle.textContent = 'Error';
            const inactive = String(error.message || '').includes('[membership_inactive]');
            const title = inactive ? 'Membresía inactiva' : 'Error de carga';
            const text = inactive
                ? 'Tu membresía ha expirado o no está activa. Renueva para acceder a las rutinas.'
                : (error.message || 'Ocurrió un error inesperado.');

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: inactive ? 'info' : 'error', title, text, confirmButtonColor: '#ffc107' });
            }

            routineContainer.innerHTML = `
        <div class="alert alert-${inactive ? 'warning' : 'danger'} text-center" role="alert">
          <h4 class="alert-heading">${escapeHtml(title)}</h4>
          <p>${escapeHtml(text)}</p>
          ${inactive ? '<a href="index.php?page=pagar_membresia" class="btn btn-warning mt-2">Renovar Ahora</a>' : ''}
        </div>`;
        });
}