document.getElementById('add-course-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Grab everything from the form (including the file!)
    const formData = new FormData(e.target);

    // Make sure the checkbox sends a 1 or 0
    formData.set('is_published', formData.get('is_published') ? 1 : 0);

    try {
        const response = await fetch('../../api/courses.php', {
            method: 'POST',
            // DO NOT set 'Content-Type' header here! 
            // The browser will automatically set it to 'multipart/form-data' so the image works.
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            alert('Course and Image Added successfully!');
            window.location.href = 'manage_courses.html';
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error(error);
        alert("A network error occurred.");
    }
});