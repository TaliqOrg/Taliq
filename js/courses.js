// This function runs as soon as the page loads
document.addEventListener('DOMContentLoaded', function() {
    container = document.getElementById('course-container');

    limit = container ? parseInt(container.getAttribute('data-limit')) || 0 : 0;
    fetchCourses(limit);
});

function fetchCourses(NumberOfCourseToDisplay=0) {
    // Call PHP API
    fetch('/taliq/api/courses.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('course-container');



            // Loop through the "records" array

            if (data.records && data.records.length > 0) {

                if (NumberOfCourseToDisplay !== 0) {
                    data.records = data.records.slice(0, NumberOfCourseToDisplay);
                }

                data.records.forEach(course => {

                    //sets whenever the course is onsite or online
                    const badgeClass = (course.CourseType === "course") ? 'badge-online' : 'badge-onsite';
                    const PlaceOfCourse = (course.CourseType === "course") ? 'Online' : 'On-Site';

                    // Create the HTML for one course card
                    const courseCard = `
                       <a href="course_details.html" class="product-card">
                            <div class="card-image-container">
                                <img class="card-img" src="${course.ThumbnailUrl || '/taliq/images/placeholder.png'}" alt="${course.Title}">
                                <span class="badge ${badgeClass}">${PlaceOfCourse}</span>
                            </div>
                            
                            <div>
                            <h3 class="card-title" >${course.Title}</h3>
                            
                            <div class="rating">

                            <!-- A star icon -->
                            <span class="material-symbols-outlined star-icon">star</span>
                            <span class="rating-score">4.9</span> <!--average score-->
                            <span class="rating-count">(1.2k)</span> <!--total number of ratings-->
                        </div>

                             <!-- price & action info -->
                        <div class="card-footer">
                            <span class="price">${course.Price} SAR</span>
                            <button class="add-cart-btn">
                                <span class="material-symbols-outlined">add_shopping_cart</span>
                            </button>
                        </div>
                        
                        
                            </div>
                                
                            
                    `;
                    // Add this card to the container
                    container.innerHTML += courseCard;
                });
            } else {
                container.innerHTML = '<p>No courses available right now.</p>';
            }
        })
        .catch(error => console.error('Error fetching courses:', error));
}
function openHelpModal() {
    document.getElementById('helpModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeHelpModal() {
    document.getElementById('helpModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

window.onclick = function(event) {
    const modal = document.getElementById('helpModal');
    if (event.target === modal) {
        closeHelpModal();
    }
}
