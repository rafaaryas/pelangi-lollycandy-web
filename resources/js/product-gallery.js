const mainImage = document.querySelector('[data-gallery-main]');
const backdropImage = document.querySelector('[data-gallery-backdrop]');
const galleryMedia = document.querySelector('[data-gallery-media]');
const thumbnails = document.querySelectorAll('[data-gallery-thumb]');

if (mainImage && galleryMedia) {
    const loaded = () => {
        const ratio = mainImage.naturalWidth / mainImage.naturalHeight;
        galleryMedia.dataset.mediaShape = ratio > 1.2 ? 'landscape' : (ratio < .83 ? 'portrait' : 'square');
        galleryMedia.classList.add('is-loaded');
    };
    if (mainImage.complete && mainImage.naturalWidth) loaded();
    else mainImage.addEventListener('load', loaded);
}

if (mainImage && thumbnails.length) {
    let changeId = 0;
    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', async () => {
            if (thumbnail.classList.contains('is-active')) return;
            const currentId = ++changeId;
            thumbnails.forEach((item) => {
                const selected = item === thumbnail;
                item.classList.toggle('is-active', selected);
                item.setAttribute('aria-pressed', String(selected));
            });
            mainImage.classList.add('is-switching');
            const nextImage = new Image();
            nextImage.src = thumbnail.dataset.image;
            await nextImage.decode().catch(() => {});
            if (currentId !== changeId) return;
            if (nextImage.naturalWidth) {
                mainImage.src = nextImage.src;
                if (backdropImage) backdropImage.src = nextImage.src;
                mainImage.alt = thumbnail.dataset.alt;
                galleryMedia.dataset.mediaShape = nextImage.naturalWidth / nextImage.naturalHeight > 1.2
                    ? 'landscape' : (nextImage.naturalWidth / nextImage.naturalHeight < .83 ? 'portrait' : 'square');
            } else {
                thumbnails.forEach((item) => {
                    const selected = item.dataset.image === mainImage.src;
                    item.classList.toggle('is-active', selected);
                    item.setAttribute('aria-pressed', String(selected));
                });
            }
            mainImage.classList.remove('is-switching');
        });
    });
}
