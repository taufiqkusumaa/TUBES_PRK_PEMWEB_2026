document.addEventListener('DOMContentLoaded', () => {
    const equipmentImagesInput = document.querySelector('#equipment-images');
    const newGalleryPreview = document.querySelector('#new-gallery-preview');
    const newGalleryGrid = document.querySelector('#new-gallery-grid');

    const refreshCoverSelection = () => {
        const checked = document.querySelector('input[name="cover_choice"]:checked:not(:disabled)');
        if (checked) return;
        const firstAvailable = document.querySelector('input[name="cover_choice"]:not(:disabled)');
        if (firstAvailable) firstAvailable.checked = true;
    };

    document.querySelectorAll('[data-delete-existing-image]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const card = checkbox.closest('[data-existing-image-card]');
            const radio = card ? card.querySelector('input[name="cover_choice"]') : null;
            if (card) card.classList.toggle('marked-for-delete', checkbox.checked);
            if (radio) {
                radio.disabled = checkbox.checked;
                if (checkbox.checked && radio.checked) radio.checked = false;
            }
            refreshCoverSelection();
        });
    });

    if (equipmentImagesInput && newGalleryPreview && newGalleryGrid) {
        equipmentImagesInput.addEventListener('change', () => {
            newGalleryGrid.innerHTML = '';
            const files = Array.from(equipmentImagesInput.files || []);
            newGalleryPreview.hidden = files.length === 0;

            files.forEach((file, index) => {
                const card = document.createElement('article');
                card.className = 'new-image-card';

                const image = document.createElement('img');
                image.alt = file.name;
                image.src = URL.createObjectURL(file);

                const name = document.createElement('span');
                name.className = 'new-image-name';
                name.textContent = file.name;

                const coverLabel = document.createElement('label');
                coverLabel.className = 'cover-choice';
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'cover_choice';
                radio.value = `new:${index}`;
                radio.checked = !document.querySelector('input[name="cover_choice"]:checked:not(:disabled)') && index === 0;
                const coverText = document.createElement('span');
                coverText.textContent = 'Foto utama';
                coverLabel.append(radio, coverText);

                const size = document.createElement('small');
                size.textContent = `${(file.size / (1024 * 1024)).toFixed(2)} MB`;
                if (file.size > 4 * 1024 * 1024) size.classList.add('file-size-error');

                card.append(image, name, coverLabel, size);
                newGalleryGrid.append(card);
            });

            refreshCoverSelection();
        });
    }
});