document.addEventListener('DOMContentLoaded', function () {
    urlParams = new URLSearchParams(window.location.search)

    populateCourseDetails();

})

function populateCourseDetails() {

    const id = urlParams.get('CourseId');
    const type = urlParams.get('CourseType');

    if (id && type) {

        fetch(`/taleeq/Taliq/api/courses.php?CourseId=${id}&CourseType=${type}`)
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    console.error("Error:", data.message);
                    return;
                }
                const badgeClass = (type === "course") ? 'badge-online' : 'badge-onsite';
                const PlaceOfCourse = (type === "course") ? 'Online' : 'On-Site';

                //put data in HTML elements
                document.getElementById('Course-Title').innerText = data.Title;
                document.getElementById('average-Rating').innerText = parseFloat(data.AverageRating).toFixed(1);
                document.getElementById('Rating-Count').innerText = "(" + data.RatingCount + ' rating)';
                document.getElementById('EnrolledAmount').innerText = data.EnrollmentCount + ' students enrolled';
                document.getElementById('Duration Time').innerText = parseFloat(data.DurationHours).toFixed(0) + " hours";
                document.getElementById('Course-Level').innerText = data.Level;
                document.getElementById('course-hero-img').src = data.ThumbnailUrl;
                document.getElementById('course-hero-img').alt = "taleeq/Taliq/images/placeholder.png";
                document.getElementById('CourseType').className = `badge course-badge ${badgeClass}`;
                document.getElementById('CourseType').innerText = PlaceOfCourse;
                document.getElementById('ItemPrice').innerText = data.Price + " SAR";
                document.getElementById('course-description').innerText = data.Description;
                document.getElementById('courseLanguage').innerText = data.Language;
                document.getElementById('HasCert').innerText = data.HasCertificate ? "Yes" : "No";

                //prints out the duration of the cours if its on-demand video or in person training
                document.getElementById("Course Duration and location").innerText =
                    `${parseFloat(data.DurationHours).toFixed(0)} hours ${type === "course" ? "on-demand video" : "In-Person Training"}`;


                //whenever this course has a Certificate or not
                let hasCert = document.getElementById('CertIncluded');
                if (data.HasCertificate) {
                    hasCert.innerHTML = '<span class="material-symbols-outlined">workspace_premium</span>\n' +
                        ' <span>Certificate of completion</span>'
                }

                //prints out the learning outcomes of said course or "what you'll learn"
                const learningoutcomesContainer = document.getElementById('learning-outcomes-container');
                const outcomesArray = JSON.parse(data.LearningOutcomes || "[]");
                if (learningoutcomesContainer) {
                    learningoutcomesContainer.innerHTML = '';

                    outcomesArray.forEach(item => {
                        learningoutcomesContainer.innerHTML += `
            <div class="outcome-item">
                <span class="material-symbols-outlined outcome-icon">check_circle</span>
                <span>${item}</span>
            </div>
        `;
                    });
                }

                //prints out whenever the course has requirements the user must have
                const RequirementsContainer = document.getElementById('RequirementToLearn');
                const RequirementsArray = JSON.parse(data.Requirements || "[]" || '["None"]');

                if(RequirementsArray){

                    RequirementsContainer.innerHTML = '';

                    let htmlString = '<ul class="requirements-list">';
                    RequirementsArray.forEach(item =>{
                        htmlString += `<li>${item}</li>`;
                    })
                    htmlString += '</ul>';

                    RequirementsContainer.innerHTML = htmlString;
                }
                else if (RequirementsContainer) {
                    // Fallback if there are no requirements
                    RequirementsContainer.innerHTML = '<p>No specific requirements.</p>';
                }


            })
            .catch(error => console.error('Error fetching details:', error));
    }
}
