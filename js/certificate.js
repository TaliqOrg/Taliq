/**
 * @file certificate.js
 * @description Fetches and renders a certificate's details on the certificate page.
 * Reads the cert_id from URL search params and populates the certificate card.
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', function () {
    const certId = new URLSearchParams(window.location.search).get('cert_id');

    if (!certId) {
        document.getElementById('certificate-card').innerHTML = '<p style="text-align:center;color:#ef4444;">Certificate ID is missing.</p>';
        return;
    }

    fetch(`/taleeq/Taliq/api/certificate.php?action=get&cert_id=${certId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('certificate-card').innerHTML = `<p style="text-align:center;color:#ef4444;">${data.message || 'Certificate not found.'}</p>`;
                return;
            }

            const cert = data.certificate;
            document.title = `Certificate - ${cert.CourseTitle}`;
            document.getElementById('cert-name').textContent   = cert.FirstName + ' ' + cert.LastName;
            document.getElementById('cert-course').textContent = cert.CourseTitle;
            document.getElementById('cert-date').textContent   = cert.IssueDateFormatted;
            document.getElementById('cert-code').textContent   = cert.CertificateCode;
        })
        .catch(() => {
            document.getElementById('certificate-card').innerHTML = '<p style="text-align:center;color:#ef4444;">Failed to load certificate.</p>';
        });
});
