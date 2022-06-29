window.onload = function() {
    document.getElementById("registration_form_picture").onchange = function(e) {
        if(!e.target.files[0].name.match(/\.(jpe?g|png|gif)$/i)) {
            alert('El tipo de fichero debe ser JPGG, PNG o GIF');

            document.getElementById('preview').src = document.getElementById('actual').src;
            e.target.value = '';
        }else {
            let reader = new FileReader();
            reader.readAsDataURL(e.target.files[0]);

            reader.onload = function() {
                let image = document.getElementById('preview');
                image.src = reader.result;
            }
        }
    }
}