import './bootstrap';

/*
 * Animations de l'interface. Alpine est fourni par Livewire : on enregistre nos
 * composants au moment ou il demarre.
 */
document.addEventListener('alpine:init', () => {
    /*
     * Compteur anime : "6 032 695 FCFA" defile de 0 jusqu'a sa valeur au chargement.
     * Le texte d'origine reste dans le HTML (lisible sans JavaScript et a l'impression),
     * seule la partie numerique est animee, le suffixe (FCFA, %) est conserve.
     */
    window.Alpine.data('compteur', (texte) => ({
        affiche: texte,
        init() {
            const correspondance = String(texte).match(/^(-?[\d\s  ]+)(.*)$/);
            if (!correspondance || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }
            const cible = parseInt(correspondance[1].replace(/[\s  ]/g, ''), 10);
            const suffixe = correspondance[2];
            if (!Number.isFinite(cible) || cible === 0) {
                return;
            }
            const duree = 900;
            const debut = performance.now();
            const etape = (maintenant) => {
                const t = Math.min(1, (maintenant - debut) / duree);
                const progression = 1 - Math.pow(1 - t, 3); // ralentit a l'arrivee
                this.affiche = Math.round(cible * progression).toLocaleString('fr-FR').replace(/ /g, ' ') + suffixe;
                if (t < 1) {
                    requestAnimationFrame(etape);
                } else {
                    this.affiche = texte;
                }
            };
            requestAnimationFrame(etape);
        },
    }));

    /*
     * Zone de scan de la caisse : retour sonore et visuel apres chaque scan, et
     * lecture par la camera du telephone quand le navigateur sait detecter les
     * codes-barres (Chrome / Android). Sinon le bouton camera n'apparait pas et la
     * douchette (qui se comporte comme un clavier) reste utilisable partout.
     */
    window.Alpine.data('scanCaisse', () => ({
        message: '',
        succes: true,
        cameraDisponible: 'BarcodeDetector' in window && !!navigator.mediaDevices?.getUserMedia,
        cameraOuverte: false,
        flux: null,
        minuteur: null,
        resultat(detail) {
            this.succes = detail.ok;
            this.message = detail.ok ? `✓ ${detail.libelle} ajouté au panier` : '';
            this.bip(detail.ok);
            clearTimeout(this.minuteur);
            this.minuteur = setTimeout(() => (this.message = ''), 2500);
            this.$nextTick(() => this.$refs.champ?.focus());
        },
        bip(ok) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = ok ? 1250 : 300;
                gain.gain.value = 0.08;
                osc.connect(gain).connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + (ok ? 0.09 : 0.25));
            } catch (e) { /* son non disponible : sans importance */ }
        },
        async ouvrirCamera() {
            try {
                this.flux = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            } catch (e) {
                this.succes = false;
                this.message = "Impossible d'ouvrir la caméra (autorisation refusée ?).";
                return;
            }
            this.cameraOuverte = true;
            const video = this.$refs.video;
            video.srcObject = this.flux;
            await video.play();
            const detecteur = new window.BarcodeDetector();
            const lire = async () => {
                if (!this.cameraOuverte) return;
                try {
                    const codes = await detecteur.detect(video);
                    if (codes.length) {
                        this.fermerCamera();
                        this.$wire.codeScan = codes[0].rawValue;
                        this.$wire.scanner();
                        return;
                    }
                } catch (e) { /* image pas encore prete */ }
                requestAnimationFrame(lire);
            };
            requestAnimationFrame(lire);
        },
        fermerCamera() {
            this.cameraOuverte = false;
            this.flux?.getTracks().forEach((piste) => piste.stop());
            this.flux = null;
        },
        destroy() {
            this.fermerCamera();
        },
    }));

    /* Horloge de l'en-tete, mise a jour chaque seconde. */
    window.Alpine.data('horloge', () => ({
        heure: '',
        init() {
            const maj = () => {
                this.heure = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            };
            maj();
            const id = setInterval(maj, 1000);
            document.addEventListener('livewire:navigating', () => clearInterval(id), { once: true });
        },
    }));
});

/*
 * Barre de chargement en haut de l'ecran pendant chaque echange avec le serveur
 * (enregistrement, filtre, recherche...), pour que l'utilisateur voie que
 * l'application travaille. La navigation entre pages a deja la sienne (Livewire).
 */
document.addEventListener('livewire:init', () => {
    let enCours = 0;
    let delai = null;

    const barre = () => document.getElementById('barre-chargement');

    window.Livewire.hook('request', ({ respond, fail }) => {
        enCours++;
        clearTimeout(delai);
        // N'apparait que si la requete dure : evite un clignotement a chaque frappe.
        delai = setTimeout(() => barre()?.classList.add('actif'), 150);

        const fin = () => {
            enCours = Math.max(0, enCours - 1);
            if (enCours === 0) {
                clearTimeout(delai);
                barre()?.classList.remove('actif');
            }
        };
        respond(fin);
        fail(fin);
    });
});

/*
 * Application installable : enregistrement du service worker (cache, hors ligne)
 * et memorisation de l'invitation a installer, pour le bouton "Installer".
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.__invitationInstallation = event;
    window.dispatchEvent(new CustomEvent('installation-disponible'));
});

window.installerApplication = async () => {
    const invitation = window.__invitationInstallation;
    if (!invitation) {
        return;
    }
    invitation.prompt();
    await invitation.userChoice;
    window.__invitationInstallation = null;
};
