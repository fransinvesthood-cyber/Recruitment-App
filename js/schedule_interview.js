// ==================== GLOBAL STATE ====================
let applicants=[], selectedApplicant=null, fpInstance=null, currentInterviewId=null;

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function(){
  loadApplicants(); initFlatpickr();
  const tt=document.getElementById('theme-toggle');
  if(tt){
    const ct=localStorage.getItem('theme');
    if(ct==='dark'){tt.checked=true; document.body.classList.add('dark-mode');}
    tt.addEventListener('change',function(){
      if(this.checked){document.body.classList.add('dark-mode'); localStorage.setItem('theme','dark');}
      else{document.body.classList.remove('dark-mode'); localStorage.setItem('theme','light');}
    });
  }
  document.getElementById('mobileMenuBtn')?.addEventListener('click',function(){
    const sb=document.getElementById('sidebar');
    sb.classList.toggle('active');
    document.getElementById('mobileMenuOverlay').style.display=sb.classList.contains('active')?'block':'none';
  });
  document.getElementById('mobileMenuOverlay')?.addEventListener('click',function(){
    document.getElementById('sidebar').classList.remove('active'); this.style.display='none';
  });
  window.addEventListener('resize',handleTabletView); handleTabletView();
  ['position','company_address','interview_date','interviewerInput'].forEach(id=>{
    const el=document.getElementById(id); if(el){el.addEventListener('input',function(){checkFormValidity(); checkAvailability();}); el.addEventListener('change',function(){checkFormValidity(); checkAvailability();});}
  });
});

function handleTabletView(){
  const sb=document.getElementById('sidebar');
  if(window.innerWidth<=992&&window.innerWidth>768) sb.classList.add('collapsed'); else sb.classList.remove('collapsed');
}

// ==================== APPLICANTS ====================
function loadApplicants(){
  fetch('fetch_shortlisted_applicants.php').then(r=>{
    if(!r.ok) throw new Error('HTTP '+r.status);
    return r.json();
  }).then(data=>{
    if(data.error){
      console.error('Server error:', data.error);
      showToast('Server error: '+data.error,'error');
      document.getElementById('applicantList').innerHTML='<div class="empty-state"><i class="bx bx-error"></i><p>Error: '+escapeHtml(data.error)+'</p></div>';
      return;
    }
    if(!Array.isArray(data)){
      console.error('Unexpected response:', data);
      showToast('Unexpected server response','error');
      return;
    }
    applicants=data;
    document.getElementById('applicantCount').textContent=data.length;
    renderApplicantList(data);
    if(data.length===0){
      document.getElementById('applicantList').innerHTML='<div class="empty-state"><i class="bx bx-user-x"></i><p>No shortlisted applicants found in database.</p><p style="font-size:12px;margin-top:8px;">Make sure applications have status = "Shortlisted" and role = "Applicant".</p></div>';
    }
  }).catch(err=>{
    console.error('Fetch error:', err);
    showToast('Failed to load applicants: '+err.message,'error');
    document.getElementById('applicantList').innerHTML='<div class="empty-state"><i class="bx bx-error"></i><p>Error loading applicants</p><p style="font-size:12px;margin-top:8px;">'+escapeHtml(err.message)+'</p></div>';
  });
}

function renderApplicantList(list){
  const c=document.getElementById('applicantList');
  if(!list.length){c.innerHTML='<div class="empty-state"><i class="bx bx-user-x"></i><p>No shortlisted applicants found.</p></div>'; return;}
  c.innerHTML=list.map(a=>`
    <div class="applicant-card ${selectedApplicant?.user_id===a.user_id?'active':''}" onclick="selectApplicant(${a.user_id})" data-name="${a.fullname.toLowerCase()}">
      <div class="applicant-card-header">
        <div class="applicant-name">${escapeHtml(a.fullname)}</div>
        <div class="match-badge ${a.match_percentage>=85?'match-high':a.match_percentage>=70?'match-medium':'match-low'}">${a.match_percentage}%</div>
      </div>
      <div class="applicant-position">${escapeHtml(a.job_position)}</div>
      <div class="applicant-meta">
        <span class="status-badge status-shortlisted">Shortlisted</span>
        <span style="font-size:12px;color:var(--gray);">${a.years_experience||0} yrs exp</span>
      </div>
      <div class="match-bar"><div class="match-fill ${a.match_percentage>=85?'high':a.match_percentage>=70?'medium':'low'}" style="width:${a.match_percentage}%"></div></div>
    </div>`).join('');
}

function filterApplicants(){
  const q=document.getElementById('applicantSearch').value.toLowerCase();
  renderApplicantList(applicants.filter(a=>a.fullname.toLowerCase().includes(q)||a.job_position.toLowerCase().includes(q)));
}

function selectApplicant(uid){
  selectedApplicant=applicants.find(a=>a.user_id===uid);
  renderApplicantList(applicants);
  document.getElementById('noSelectionCard').style.display='none';
  document.getElementById('detailsCard').style.display='block';
  document.getElementById('formCard').style.display='block';
  document.getElementById('formUserId').value=uid;
  document.getElementById('displayName').value=selectedApplicant.fullname;
  loadApplicantDetails(uid);
  loadPositions(uid);
  checkFormValidity();
}

function loadApplicantDetails(uid){
  document.getElementById('summaryGrid').innerHTML='<div class="summary-item"><div class="summary-label">Loading...</div></div>';
  fetch('fetch_applicant_details.php?user_id='+uid).then(r=>r.json()).then(data=>{
    if(data.error){showToast(data.error,'error'); return;}
    const sg=document.getElementById('summaryGrid');
    sg.innerHTML=`
      <div class="summary-item"><div class="summary-label">Match Score</div><div class="summary-value" style="color:${data.match_percentage>=85?'var(--success)':data.match_percentage>=70?'var(--warning)':'var(--danger)'}">${data.match_percentage}%</div></div>
      <div class="summary-item"><div class="summary-label">Experience</div><div class="summary-value">${data.years_experience} years</div></div>
      <div class="summary-item"><div class="summary-label">Position</div><div class="summary-value">${escapeHtml(data.job_position)}</div></div>
      <div class="summary-item"><div class="summary-label">Title</div><div class="summary-value">${escapeHtml(data.professional_title)}</div></div>`;
    const ss=document.getElementById('skillsSection');
    const m=data.skills_match||{};
    ss.innerHTML=`<p style="font-size:13px;color:var(--gray);margin-bottom:6px;"><strong>Matched:</strong> ${m.matched?.length||0} / ${m.total_required||0} skills</p>
      <div class="skills-list">${(m.matched||[]).map(s=>`<span class="skill-tag">${escapeHtml(s)}</span>`).join('')}${(m.missing||[]).map(s=>`<span class="skill-tag missing">${escapeHtml(s)}</span>`).join('')}</div>`;
    if(data.professional_summary){
      ss.innerHTML+=`<p style="margin-top:12px;font-size:13px;color:var(--gray);line-height:1.5;"><strong>Summary:</strong> ${escapeHtml(data.professional_summary)}</p>`;
    }
  }).catch(()=>showToast('Failed to load applicant details','error'));
}

function loadPositions(uid){
  const ps=document.getElementById('position'); ps.innerHTML='<option value="">-- Loading... --</option>'; ps.disabled=true;
  fetch('fetch_shortlisted_job.php?user_id='+uid).then(r=>r.json()).then(data=>{
    ps.innerHTML='<option value="">-- Select Position --</option>'+data.map(j=>`<option value="${j.job_id}">${escapeHtml(j.position)}</option>`).join('');
    ps.disabled=false;
    if(data.length===1){ps.value=data[0].job_id; checkFormValidity();}
  }).catch(()=>{ps.innerHTML='<option value="">Error loading</option>'; ps.disabled=false;});
}

// ==================== INTERVIEW TYPE ====================
function setInterviewType(type){
  document.getElementById('interviewType').value=type;
  document.getElementById('btnInPerson').classList.toggle('active',type==='In-person');
  document.getElementById('btnOnline').classList.toggle('active',type==='Online');
  document.getElementById('addressGroup').style.display=type==='In-person'?'block':'none';
  document.getElementById('meetingLinkGroup').style.display=type==='Online'?'block':'none';
  if(type==='In-person') document.getElementById('company_address').required=true; else document.getElementById('company_address').required=false;
  if(type==='Online') document.getElementById('meeting_link').required=true; else document.getElementById('meeting_link').required=false;
  checkFormValidity();
}

// ==================== DATE PICKER ====================
function initFlatpickr(){
  fpInstance=flatpickr('#interview_date',{enableTime:true,dateFormat:'Y-m-d H:i',minDate:'today',time_24hr:false,minuteIncrement:15,onChange:function(){checkAvailability(); checkFormValidity();}});
}

// ==================== AVAILABILITY ====================
let availTimeout=null;
function checkAvailability(){
  const date=document.getElementById('interview_date').value;
  const names=document.getElementById('interviewerInput').value.trim();
  const dur=document.getElementById('duration_minutes').value;
  const res=document.getElementById('availabilityResult');
  if(!date||!names){res.innerHTML='<i class="bx bx-time-five"></i> Select date & interviewers to check'; res.style.background='var(--light)'; res.style.color='var(--gray)'; return;}
  res.innerHTML='<i class="bx bx-loader-alt bx-spin"></i> Checking...';
  clearTimeout(availTimeout);
  availTimeout=setTimeout(()=>{
    fetch(`check_interviewer_availability.php?interviewer_names=${encodeURIComponent(names)}&date_time=${encodeURIComponent(date)}&duration=${dur}&exclude_id=${currentInterviewId||''}`)
      .then(r=>r.json()).then(data=>{
        if(data.available){
          res.innerHTML='<i class="bx bx-check-circle"></i> '+escapeHtml(data.message);
          res.style.background='#d4edda'; res.style.color='#155724';
        }else{
          let html='<i class="bx bx-error-circle"></i> Conflicts:<ul style="margin:4px 0 0 16px;">';
          data.conflicts.forEach(c=>{
            if(c.type==='unavailable') html+=`<li>${escapeHtml(c.interviewer_name)}: ${escapeHtml(c.reason)}</li>`;
            else html+=`<li>With ${escapeHtml(c.candidate_name||'another candidate')} at ${escapeHtml(c.existing_time)}</li>`;
          });
          html+='</ul>';
          res.innerHTML=html; res.style.background='#f8d7da'; res.style.color='#721c24';
        }
      }).catch(()=>{res.innerHTML='<i class="bx bx-error"></i> Check failed'; res.style.background='#f8d7da';});
  },500);
}

// ==================== FORM VALIDATION ====================
function checkFormValidity(){
  const pos=document.getElementById('position').value;
  const type=document.getElementById('interviewType').value;
  const addr=document.getElementById('company_address').value.trim();
  const link=document.getElementById('meeting_link').value.trim();
  const ivs=document.getElementById('interviewerInput').value.trim();
  const dt=document.getElementById('interview_date').value;
  const valid=pos && ivs && dt && (type==='In-person'?addr:link);
  document.getElementById('submitBtn').disabled=!valid;
  return valid;
}

function validateAndSubmit(e){
  e.preventDefault();
  if(!checkFormValidity()){showToast('Please fill in all required fields','error'); return false;}
  const res=document.getElementById('availabilityResult');
  if(res.style.color==='rgb(114, 28, 36)'||res.style.color==='#721c24'){
    Swal.fire({title:'Scheduling Conflict',text:'There are conflicts with the selected interviewers. Schedule anyway?',icon:'warning',showCancelButton:true,confirmButtonText:'Schedule Anyway',cancelButtonText:'Cancel'}).then(r=>{if(r.isConfirmed) e.target.submit();});
    return false;
  }
  e.target.submit(); return true;
}

// ==================== ACTIONS ====================
function handleReschedule(){showToast('Use the Reschedule button on Scheduled Interviews page','info');}
function handleCancel(){showToast('Use the Cancel button on Scheduled Interviews page','info');}

// ==================== TOAST ====================
function showToast(msg,type){
  const tc=document.getElementById('toastContainer');
  const t=document.createElement('div'); t.className='toast toast-'+type;
  const icons={success:'bx-check-circle',error:'bx-x-circle',info:'bx-info-circle',warning:'bx-error'};
  t.innerHTML=`<i class="bx ${icons[type]||'bx-info-circle'}"></i>${escapeHtml(msg)}`;
  tc.appendChild(t); setTimeout(()=>t.remove(),5000);
}

function escapeHtml(t){const d=document.createElement('div'); d.textContent=t; return d.innerHTML;}
function confirmLogout(){return confirm('Are you sure you want to log out?');}
