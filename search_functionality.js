// Global Search Functionality for Admin Dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Search functionality initializing...');
    
    const searchInput = document.getElementById('globalSearchInput');
    const searchBtn = document.getElementById('searchBtn');
    const searchResults = document.getElementById('searchResults');
    
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
        const query = e.target.value.trim();
        clearTimeout(searchTimeout);
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                console.log('Searching for:', query);
                searchResults.innerHTML = '<div class="search-loading"><i class="bx bx-loader"></i> Searching...</div>';
                searchResults.classList.add('active');
                
                fetch('search_handler.php?q=' + encodeURIComponent(query))
                    .then(res => {
                        console.log('Response status:', res.status);
                        if (!res.ok) {
                            throw new Error('HTTP error! status: ' + res.status);
                        }
                        return res.text();
                    })
                    .then(text => {
                        console.log('Raw response:', text);
                        if (!text || text.trim() === '') {
                            throw new Error('Empty response from server');
                        }
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        
                        // Check for error in response
                        if (data.error) {
                            searchResults.innerHTML = '<div class="search-no-results">Error: ' + data.error + '</div>';
                            return;
                        }
                        
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
                        if (html === '') {
                            html = '<div class="search-no-results"><i class="bx bx-search"></i> No results found for "' + query + '"</div>';
                        }
                        searchResults.innerHTML = html;
                    })
                    .catch(err => { 
                        console.error('Search error:', err); 
                        searchResults.innerHTML = '<div class="search-no-results"><i class="bx bx-error"></i> Search error: ' + err.message + '</div>'; 
                    });
            }, 300);
        } else {
            searchResults.classList.remove('active');
        }
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) searchResults.classList.add('active');
    });
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
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
});
