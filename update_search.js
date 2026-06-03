const searchJs = `// Global Search Functionality for Admin Dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Search functionality initializing...');
    
    const searchInput = document.getElementById('globalSearchInput');
    const searchBtn = document.getElementById('searchBtn');
    const searchResults = document.getElementById('searchResults');
    
    console.log('searchInput:', searchInput);
    console.log('searchBtn:', searchBtn);
    console.log('searchResults:', searchResults);
    
    if (!searchInput) {
        console.error('Search input not found!');
        return;
    }
    if (!searchResults) {
        console.error('Search results container not found!');
        return;
    }
    
    let searchTimeout;
    searchInput.addEventListener('input', function(e) {
        console.log('Input event triggered, value:', e.target.value);
        const query = e.target.value.trim();
        clearTimeout(searchTimeout);
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                console.log('Searching for:', query);
                searchResults.innerHTML = '<div class="search-loading"><i class="bx bx-loader"></i></div>';
                searchResults.classList.add('active');
                
                fetch('search_handler.php?q=' + encodeURIComponent(query))
                    .then(res => {
                        console.log('Response status:', res.status);
                        return res.json();
                    })
                    .then(data => {
                        console.log('Search data:', data);
                        let html = '';
                        if (data.jobs && data.jobs.length > 0) {
                            html += '<div class="search-result-section"><div class="search-result-header"><i class="bx bx-briefcase"></i> Jobs</div>';
                            data.jobs.forEach(job => {
                                html += '<a href="manage_jobs.php?job_id=' + job.job_id + '" class="search-result-item"><div class="search-result-icon job"><i class="bx bx-briefcase"></i></div><div class="search-result-details"><div class="search-result-title">' + job.position + '</div></div></a>';
                            });
                            html += '</div>';
                        }
                        if (data.candidates && data.candidates.length > 0) {
                            html += '<div class="search-result-section"><div class="search-result-header"><i class="bx bx-user"></i> Candidates</div>';
                            data.candidates.forEach(c => {
                                html += '<a href="view_applicant_profile.php?user_id=' + c.user_id + '" class="search-result-item"><div class="search-result-icon candidate"><i class="bx bx-user"></i></div><div class="search-result-details"><div class="search-result-title">' + c.fullname + '</div></div></a>';
                            });
                            html += '</div>';
                        }
                        if (data.applications && data.applications.length > 0) {
                            html += '<div class="search-result-section"><div class="search-result-header"><i class="bx bx-file"></i> Applications</div>';
                            data.applications.forEach(a => {
                                html += '<a href="manage_applications.php?application_id=' + a.application_id + '" class="search-result-item"><div class="search-result-icon application"><i class="bx bx-file"></i></div><div class="search-result-details"><div class="search-result-title">' + (a.candidate_name || 'Application') + '</div></div></a>';
                            });
                            html += '</div>';
                        }
                        if (data.users && data.users.length > 0) {
                            html += '<div class="search-result-section"><div class="search-result-header"><i class="bx bx-users"></i> Users</div>';
                            data.users.forEach(u => {
                                html += '<a href="my_profile.php?user_id=' + u.user_id + '" class="search-result-item"><div class="search-result-icon user"><i class="bx bx-user"></i></div><div class="search-result-details"><div class="search-result-title">' + u.fullname + '</div><div class="search-result-meta">' + u.role + '</div></div></a>';
                            });
                            html += '</div>';
                        }
                        if (data.leave_requests && data.leave_requests.length > 0) {
                            html += '<div class="search-result-section"><div class="search-result-header"><i class="bx bx-calendar-minus"></i> Leave Requests</div>';
                            data.leave_requests.forEach(l => {
                                html += '<a href="view_leave_request.php?id=' + l.consult_leave_id + '" class="search-result-item"><div class="search-result-icon leave"><i class="bx bx-calendar-minus"></i></div><div class="search-result-details"><div class="search-result-title">' + l.fullname + '</div><div class="search-result-meta">' + l.leave_type + '</div></div></a>';
                            });
                            html += '</div>';
                        }
                        if (html === '') html = '<div class="search-no-results">No results found</div>';
                        searchResults.innerHTML = html;
                    })
                    .catch(err => { 
                        console.error('Search error:', err); 
                        searchResults.innerHTML = '<div class="search-no-results">Search error</div>'; 
                    });
            }, 300);
        } else {
            searchResults.classList.remove('active');
        }
    });
    
    searchInput.addEventListener('focus', function() {
        console.log('Focus event, value:', this.value);
        if (this.value.trim().length >= 2) searchResults.classList.add('active');
    });
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            console.log('Search button clicked');
            const query = searchInput.value.trim();
            if (query.length >= 2) searchResults.classList.add('active');
        });
    }
    
    document.addEventListener('click', function(e) {
        if (searchResults && searchInput && !searchResults.contains(e.target) && !searchInput.contains(e.target)) {
            searchResults.classList.remove('active');
        }
    });
    
    console.log('Search functionality initialized');
});`;

const fs = require('fs');
fs.writeFileSync('search_functionality.js', searchJs);
console.log('Updated search_functionality.js');
