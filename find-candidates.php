<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Candidates | Candidit</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --dark: #111827;
            --gray-50: #F9FAFB;
            --white: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--gray-50); color: var(--dark); display: flex; }

        /* --- SIDEBAR FILTERS --- */
        .sidebar { width: 300px; height: 100vh; background: white; border-right: 1px solid #E5E7EB; padding: 30px; position: fixed; }
        .sidebar h2 { font-size: 1.2rem; font-weight: 800; margin-bottom: 25px; }
        
        label { display: block; font-weight: 700; font-size: 0.75rem; color: #6B7280; text-transform: uppercase; margin-bottom: 8px; margin-top: 20px; }
        select, input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #D1D5DB; margin-bottom: 15px; }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: 300px; width: calc(100% - 300px); padding: 40px 60px; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }

        .candidate-card { background: white; border-radius: 16px; border: 1px solid #E5E7EB; padding: 30px; margin-bottom: 20px; transition: 0.3s; }
        .candidate-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.05); border-color: var(--primary); }

        .card-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .match-pill { background: #EEF2FF; color: var(--primary); padding: 6px 16px; border-radius: 100px; font-weight: 800; font-size: 0.9rem; }
        
        .name { font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
        .role { color: #6B7280; font-weight: 500; margin-bottom: 15px; }

        .tags { display: flex; gap: 8px; margin-bottom: 20px; }
        .tag { background: #F3F4F6; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }

        .why-match { background: #F9FAFB; padding: 15px; border-radius: 12px; margin-top: 20px; border-left: 4px solid var(--primary); }
        .why-match h4 { font-size: 0.8rem; text-transform: uppercase; color: #6B7280; margin-bottom: 8px; }
        .why-match li { font-size: 0.9rem; color: #374151; list-style: none; display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .why-match li::before { content: '✓'; color: #10B981; font-weight: bold; }

        .action-btns { display: flex; gap: 10px; margin-top: 25px; }
        .btn-view { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .btn-outline { background: white; border: 1px solid #D1D5DB; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>Filters</h2>
        
        <label>Job Role</label>
        <input type="text" value="Software Engineer">

        <label>Min Match Score</label>
        <select>
            <option>90% +</option>
            <option>80% +</option>
            <option>All candidates</option>
        </select>

        <label>Location</label>
        <input type="text" placeholder="Johannesburg, SA">

        <label>Experience</label>
        <select>
            <option>Senior (5+ yrs)</option>
            <option>Mid-level</option>
            <option>Junior</option>
        </select>

        <button style="background: var(--dark); color: white; width: 100%; padding: 12px; border-radius: 8px; border: none; font-weight: 700; margin-top: 20px; cursor: pointer;">Apply Filters</button>
    </aside>

    <main class="main-content">
        <div class="header-row">
            <h1>Ranked Candidates</h1>
            <p style="color: #6B7280; font-weight: 600;">34 potential matches found</p>
        </div>

        <div class="candidate-card">
            <div class="card-header">
                <div>
                    <div class="name">Alex Rivera</div>
                    <div class="role">Full Stack Engineer • Cape Town, South Africa</div>
                </div>
                <div class="match-pill">99% Match</div>
            </div>
            
            <div class="tags">
                <span class="tag">React</span>
                <span class="tag">Node.js</span>
                <span class="tag">AWS</span>
                <span class="tag">PostgreSQL</span>
            </div>

            <div class="why-match">
                <h4>Why this match?</h4>
                <ul>
                    <li>Matches 100% of required technical skills</li>
                    <li>Strong experience level (6 years in fintech)</li>
                    <li>Previously worked in high-scale startup environments</li>
                </ul>
            </div>

            <div class="action-btns">
                <button class="btn-view">View Full Profile</button>
                <button class="btn-outline">Shortlist</button>
                <button class="btn-outline" style="color: #EF4444;">Reject</button>
            </div>
        </div>

        <div class="candidate-card">
            <div class="card-header">
                <div>
                    <div class="name">Jordan Smith</div>
                    <div class="role">Software Engineer • Johannesburg, South Africa</div>
                </div>
                <div class="match-pill">96% Match</div>
            </div>
            
            <div class="tags">
                <span class="tag">React</span>
                <span class="tag">TypeScript</span>
                <span class="tag">UI/UX Focus</span>
            </div>

            <div class="why-match">
                <h4>Why this match?</h4>
                <ul>
                    <li>Expertise in your specific frontend stack</li>
                    <li>Local to Johannesburg office</li>
                    <li>Strong culture-fit score from previous reviews</li>
                </ul>
            </div>

            <div class="action-btns">
                <button class="btn-view">View Full Profile</button>
                <button class="btn-outline">Shortlist</button>
                <button class="btn-outline" style="color: #EF4444;">Reject</button>
            </div>
        </div>

    </main>
</body>
</html>