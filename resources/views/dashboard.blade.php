@extends('layouts.app-dashboard')

@section('content')
<div class="dashboard-content">
    <div class="dashboard-left">
        <div class="welcome-card">
            <div>
                <h2>Hi, Academic Manager</h2>
                <p>Monitor instructor progress, active classes, and student performance in one place.</p>
            </div>

            <div class="top-filters">
                <select>
                    <option>Weekly analysis</option>
                    <option>Monthly analysis</option>
                </select>

                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search">
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-label">Instructor Completion Rate</div>
                <div class="stat-value">84.02%</div>
                <div class="stat-desc">Compared to last week +5.32%</div>
                <div class="mini-wave"></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Active Instructors</div>
                <div class="stat-value">16</div>
                <div class="stat-desc">4 instructors need attention</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Students Monitored</div>
                <div class="stat-value">349</div>
                <div class="stat-desc">Across all running programs</div>
            </div>
        </div>

        <div class="summary-grid">
            <div class="panel-card">
                <div class="panel-head">
                    <h3>Instructor Performance</h3>
                    <a href="#">View all</a>
                </div>

                <div class="bar-list">
                    <div class="bar-item">
                        <span>Module Delivery</span>
                        <div class="bar"><div style="width: 88%;"></div></div>
                        <strong>88%</strong>
                    </div>
                    <div class="bar-item">
                        <span>Assignment Review</span>
                        <div class="bar"><div style="width: 72%;"></div></div>
                        <strong>72%</strong>
                    </div>
                    <div class="bar-item">
                        <span>Attendance Update</span>
                        <div class="bar"><div style="width: 91%;"></div></div>
                        <strong>91%</strong>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-head">
                    <h3>Program Status</h3>
                    <a href="#">Details</a>
                </div>

                <div class="program-list">
                    <div class="program-item">
                        <span>Software Engineering</span>
                        <strong>12 Classes</strong>
                    </div>
                    <div class="program-item">
                        <span>UI/UX Design</span>
                        <strong>8 Classes</strong>
                    </div>
                    <div class="program-item">
                        <span>Flutter Development</span>
                        <strong>5 Classes</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="panel-head">
                <h3>Current Monitoring</h3>
                <a href="#">Export</a>
            </div>

            <div class="monitor-table">
                <div class="monitor-row monitor-head">
                    <span>Instructor</span>
                    <span>Class</span>
                    <span>Progress</span>
                    <span>Status</span>
                </div>

                <div class="monitor-row">
                    <span>Rizky</span>
                    <span>SE Batch 12</span>
                    <span>78%</span>
                    <span><label class="badge success">On Track</label></span>
                </div>

                <div class="monitor-row">
                    <span>Nadia</span>
                    <span>UI/UX Batch 8</span>
                    <span>63%</span>
                    <span><label class="badge warning">Review</label></span>
                </div>

                <div class="monitor-row">
                    <span>Farhan</span>
                    <span>Flutter Batch 4</span>
                    <span>90%</span>
                    <span><label class="badge success">On Track</label></span>
                </div>

                <div class="monitor-row">
                    <span>Dina</span>
                    <span>SE Batch 11</span>
                    <span>48%</span>
                    <span><label class="badge danger">Late</label></span>
                </div>
            </div>
        </div>
    </div>

    <aside class="dashboard-right">
        <div class="highlight-card">
            <div class="highlight-content">
                <p class="small-label">Overall Monitoring Score</p>
                <h3>92.4</h3>
                <span>Excellent operational visibility</span>
            </div>
        </div>

        <div class="side-card">
            <div class="panel-head">
                <h3>Recent Updates</h3>
            </div>

            <div class="activity-list">
                <div class="activity-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <strong>SE Batch 12</strong>
                        <p>Module 4 has been completed</p>
                    </div>
                </div>

                <div class="activity-item">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div>
                        <strong>UI/UX Batch 8</strong>
                        <p>Assignment feedback delayed</p>
                    </div>
                </div>

                <div class="activity-item">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <strong>Flutter Batch 4</strong>
                        <p>Attendance updated 1 hour ago</p>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>
@endsection