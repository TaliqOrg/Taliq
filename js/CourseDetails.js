document.addEventListener('DOMContentLoaded', function () {
    urlParams = new URLParams(window.location.search)
    console.log(urlParams);
    const id = urlParams.get('id');
    const type = urlParams.get('type');

    console.log(id);
})