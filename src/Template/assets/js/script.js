document.addEventListener('DOMContentLoaded', (event) => {
    date = document.getElementById('date');
    refresh =  document.getElementById('refresh-data');

    date.addEventListener('change',(e) => {
        window.location.href = 'index.php?c=cases&m=dailyCaseReport&d='+date.value;
    })
    refresh.addEventListener('click',(e) => {
        e.preventDefault();
        fetch('index.php?c=cases&m=updateData')
        .then(response => response.text())
        .then(data => {
            if(data == 'success') {
                window.location.reload();
            }
        })
        .catch(e => console.log(e));
    })
    
})