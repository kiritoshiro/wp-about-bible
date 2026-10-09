/* The WordPress media library owns upload validation and attachment permissions. */
document.addEventListener('click', function (event) {
    const button = event.target.closest('.ab-select-image, .ab-remove-image');
    if (!button) return;
    const field = button.closest('.ab-topic-media');
    const input = field.querySelector('input');
    const preview = field.querySelector('.ab-topic-preview');
    if (button.classList.contains('ab-remove-image')) {
        input.value = '0';
        preview.replaceChildren();
        return;
    }
    const frame = wp.media({title: 'Temos paveikslėlis', library: {type: 'image'}, multiple: false});
    frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        input.value = String(attachment.id);
        const image = document.createElement('img');
        image.src = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
        image.alt = '';
        image.width = 150;
        preview.replaceChildren(image);
    });
    frame.open();
});
