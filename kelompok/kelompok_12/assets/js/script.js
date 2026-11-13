document.addEventListener("DOMContentLoaded", function() {
    console.log("Portal Sehatin Frontend Berhasil Dimuat.");

    // Efek interaktif animasi halus saat kursor menyentuh kartu artikel
    const articleCards = document.querySelectorAll('.card-article');
    articleCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            card.style.transform = 'translateY(-4px)';
            card.style.transition = 'transform 0.25s ease-in-out';
            card.style.boxShadow = '0 .5rem 1rem rgba(0,0,0,.15)';
        });
        card.addEventListener('mouseleave', function() {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '0 .125rem .25rem rgba(0,0,0,.075)';
        });
    });
});