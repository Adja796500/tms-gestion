/*
 * Service worker de l'application installable.
 *
 * - Fichiers statiques (CSS/JS compiles, icones, photos) : servis depuis le cache
 *   puis rafraichis en arriere-plan -> chargement rapide meme avec une connexion faible.
 * - Pages : le reseau d'abord (donnees a jour) ; si le reseau ne repond pas en
 *   quelques secondes, la derniere version consultee ; sinon la page hors ligne.
 * - Les echanges Livewire (enregistrements) et tout ce qui n'est pas GET ne passent
 *   jamais par le cache : aucune saisie ne peut etre perdue ou rejouee.
 */
const VERSION = 'v2'; // v2 : icones au logo TMS
const CACHE_STATIQUE = `statique-${VERSION}`;
const CACHE_PAGES = `pages-${VERSION}`;
const PAGE_HORS_LIGNE = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_STATIQUE)
            .then((cache) => cache.addAll([PAGE_HORS_LIGNE, '/images/logo.png', '/icons/icon-192.png', '/favicon.png']))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cles) => Promise.all(cles.filter((c) => ![CACHE_STATIQUE, CACHE_PAGES].includes(c)).map((c) => caches.delete(c))))
            .then(() => self.clients.claim())
    );
});

// A la deconnexion, la page demande d'oublier les pages en cache (donnees privees).
self.addEventListener('message', (event) => {
    if (event.data === 'vider-pages') {
        event.waitUntil(caches.delete(CACHE_PAGES));
    }
});

const estStatique = (url) => /^\/(build|icons|storage)\//.test(url.pathname) || /\.(png|jpe?g|webp|svg|ico|woff2?)$/.test(url.pathname);

function reseauAvecDelai(requete, delaiMs) {
    return new Promise((resoudre, rejeter) => {
        const minuteur = setTimeout(() => rejeter(new Error('delai')), delaiMs);
        fetch(requete).then((reponse) => { clearTimeout(minuteur); resoudre(reponse); }, (erreur) => { clearTimeout(minuteur); rejeter(erreur); });
    });
}

self.addEventListener('fetch', (event) => {
    const requete = event.request;
    const url = new URL(requete.url);

    if (requete.method !== 'GET' || url.origin !== self.location.origin || url.pathname.startsWith('/livewire')) {
        return;
    }

    if (estStatique(url)) {
        event.respondWith(
            caches.open(CACHE_STATIQUE).then(async (cache) => {
                const enCache = await cache.match(requete);
                const reseau = fetch(requete).then((reponse) => {
                    if (reponse.ok) cache.put(requete, reponse.clone());
                    return reponse;
                }).catch(() => enCache);
                return enCache || reseau;
            })
        );
        return;
    }

    if (requete.mode === 'navigate') {
        event.respondWith(
            reseauAvecDelai(requete, 6000)
                .then((reponse) => {
                    // On ne garde que les vraies pages (pas les redirections vers la connexion, ni les fichiers telecharges).
                    if (reponse.ok && !reponse.redirected && (reponse.headers.get('content-type') || '').includes('text/html')) {
                        const copie = reponse.clone();
                        caches.open(CACHE_PAGES).then((cache) => cache.put(requete, copie));
                    }
                    return reponse;
                })
                .catch(async () => (await caches.match(requete, { cacheName: CACHE_PAGES })) || caches.match(PAGE_HORS_LIGNE))
        );
    }
});
