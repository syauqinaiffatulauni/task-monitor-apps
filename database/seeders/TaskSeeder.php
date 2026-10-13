<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        Task::withTrashed()->forceDelete();

        $officers = User::query()->where('role', 'officer')->orderBy('email')->get();

        if ($officers->isEmpty()) {
            return;
        }

        $officerMap = [
            1 => $officers->firstWhere('email', 'officer@example.test') ?? $officers[0],
            2 => $officers->firstWhere('email', 'officer2@example.test') ?? ($officers[1] ?? $officers[0]),
            3 => $officers->firstWhere('email', 'officer3@example.test') ?? ($officers[2] ?? $officers[0]),
            4 => $officers->firstWhere('email', 'officer4@example.test') ?? ($officers[3] ?? $officers[0]),
        ];

        $today = today();
        $startOfWeek = now()->startOfWeek();

        $definitions = [
            // --- Group A: Overdue tasks (due_date < today) ---
            [
                'title' => 'Audit Q3 departmental expenditure and supplier invoices',
                'description' => 'Compile expenditure reconciliation reports and verify supplier invoice receipts.',
                'officer' => 1,
                'offset' => -10,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'stale' => true,
                'note' => 'Initial review started two weeks ago; waiting on finance reconciliation.',
            ],
            [
                'title' => 'Investigate unresolved public enquiry regarding municipal drainage',
                'description' => 'Perform site inspection and prepare findings report for district engineering team.',
                'officer' => 1,
                'offset' => -7,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::ToDo,
                'stale' => true,
                'note' => null,
            ],
            [
                'title' => 'Draft internal workflow guidelines for citizen grievance escalation',
                'description' => 'Standardize service escalation intervals and tier-2 contact channels.',
                'officer' => 1,
                'offset' => -5,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Drafted section 1 and 2 yesterday; legal unit review pending.',
            ],
            [
                'title' => 'Review compliance certification for cloud data hosting servers',
                'description' => 'Verify annual ISO/IEC 27001 certificate renewal and vendor security audit.',
                'officer' => 1,
                'offset' => -3,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::ToDo,
                'stale' => false,
                'note' => 'Gathered vendor documentation earlier this week.',
            ],
            [
                'title' => 'Prepare briefing deck on municipal infrastructure maintenance',
                'description' => 'Synthesize road resurfacing and street lighting maintenance milestones.',
                'officer' => 1,
                'offset' => -2,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Compiled slides 1 through 10; reviewing with lead supervisor.',
            ],
            [
                'title' => 'Collate officer KPI progress reports for mid-year evaluation',
                'description' => 'Collect department submissions and aggregate team metric scorecards.',
                'officer' => 2,
                'offset' => -4,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'stale' => true,
                'note' => 'Pending input from 2 field officers since last week.',
            ],
            [
                'title' => 'Renew enterprise software licenses and review vendor SLA',
                'description' => 'Confirm user license headcounts and negotiate maintenance renewals.',
                'officer' => 3,
                'offset' => -6,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::ToDo,
                'stale' => true,
                'note' => null,
            ],
            [
                'title' => 'Assess cyber security incident response and patch perimeter firewall',
                'description' => 'Deploy high-priority firmware patch and run external vulnerability scan.',
                'officer' => 4,
                'offset' => -2,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Patched primary gateway yesterday; secondary node testing scheduled.',
            ],
            [
                'title' => 'Verify citizen registry database migration consistency',
                'description' => 'Execute checksum verification across primary and secondary storage replicas.',
                'officer' => 1,
                'offset' => -14,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
                'stale' => false,
                'note' => 'Migration verified and signed off by lead engineer.',
            ],
            [
                'title' => 'Archive resolved legacy support tickets from previous fiscal year',
                'description' => 'Perform batch export and compress closed ticket attachments to cold storage.',
                'officer' => 2,
                'offset' => -8,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
                'stale' => false,
                'note' => 'Archival completed and stored in secure repository.',
            ],

            // --- Group B1: Due soon (today to +3 days) ---
            [
                'title' => 'Respond to priority urgent feedback on public transport scheduling',
                'description' => 'Review route congestion reports and draft immediate advisory response.',
                'officer' => 1,
                'offset' => 0,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Coordinating with transit operations desk today.',
            ],
            [
                'title' => 'Finalize draft schedule for upcoming inter-agency steering committee',
                'description' => 'Distribute confirmed agenda topics and participant RSVP calendar invites.',
                'officer' => 1,
                'offset' => 1,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::ToDo,
                'stale' => false,
                'note' => 'Draft agenda finalized this morning.',
            ],
            [
                'title' => 'Inspect security access logs for administrative portal accounts',
                'description' => 'Audit privileged user logins and flag anomalous multi-factor authentication alerts.',
                'officer' => 1,
                'offset' => 3,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::InProgress,
                'stale' => true,
                'note' => 'Preliminary report initiated last week.',
            ],
            [
                'title' => 'Update standard operating procedures for public counter operations',
                'description' => 'Incorporate updated queue ticketing protocols and visitor sign-in rules.',
                'officer' => 2,
                'offset' => 2,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::ToDo,
                'stale' => false,
                'note' => 'Drafting revised counter workflow.',
            ],
            [
                'title' => 'Review quarterly training curriculum for incoming administrative staff',
                'description' => 'Evaluate training feedback forms and update induction slide decks.',
                'officer' => 1,
                'offset' => 1,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
                'stale' => false,
                'note' => 'Curriculum approved and distributed.',
            ],

            // --- Group B2: Due in 4 to 7 days ---
            [
                'title' => 'Coordinate venue logistics and audio-visual setup for town hall meeting',
                'description' => 'Confirm microphone equipment, projection facilities, and seating arrangements.',
                'officer' => 1,
                'offset' => 4,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Audio-visual supplier booked yesterday.',
            ],
            [
                'title' => 'Conduct usability walkthrough on citizen service mobile portal',
                'description' => 'Test payment gateway flow and accessibility contrast across mobile screens.',
                'officer' => 1,
                'offset' => 5,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::ToDo,
                'stale' => true,
                'note' => null,
            ],
            [
                'title' => 'Consolidate stakeholder responses on municipal parking tariff proposal',
                'description' => 'Group survey feedback by residential zoning and commercial districts.',
                'officer' => 1,
                'offset' => 6,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Received 80% of stakeholder questionnaires.',
            ],
            [
                'title' => 'Submit preliminary budget allocation for community outreach programs',
                'description' => 'Allocate funding across youth sports clinics and senior citizen activities.',
                'officer' => 2,
                'offset' => 7,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::ToDo,
                'stale' => false,
                'note' => 'Budget numbers updated this week.',
            ],
            [
                'title' => 'Compile quarterly asset inventory report across district offices',
                'description' => 'Verify serial numbers of hardware equipment and decommissioned devices.',
                'officer' => 3,
                'offset' => 5,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
                'stale' => false,
                'note' => 'Inventory count finished and reconciled.',
            ],

            // --- Group C: Due later (> 7 days) ---
            [
                'title' => 'Formulate disaster recovery drills and emergency communication tree',
                'description' => 'Plan bi-annual staff evacuation simulation and update crisis contact list.',
                'officer' => 1,
                'offset' => 10,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Initial drill protocol drafted yesterday.',
            ],
            [
                'title' => 'Review legal advisory opinion on data privacy compliance standard',
                'description' => 'Assess impact of statutory personal data updates on existing form disclosures.',
                'officer' => 1,
                'offset' => 14,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::ToDo,
                'stale' => false,
                'note' => 'Awaiting finalized legal memo.',
            ],
            [
                'title' => 'Develop self-service knowledge base articles for frequently asked queries',
                'description' => 'Write clear guide documentation for online council tax and license renewals.',
                'officer' => 1,
                'offset' => 21,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::InProgress,
                'stale' => true,
                'note' => 'Outlined table of contents earlier this month.',
            ],
            [
                'title' => 'Conduct quarterly inspection on public library facility maintenance',
                'description' => 'Check air ventilation filters, emergency lighting, and fire extinguisher seals.',
                'officer' => 1,
                'offset' => 30,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::ToDo,
                'stale' => false,
                'note' => 'Inspection date confirmed with branch manager.',
            ],
            [
                'title' => 'Review proposals for municipal waste management service contract',
                'description' => 'Compare technical specifications, green disposal methods, and recycling quotas.',
                'officer' => 2,
                'offset' => 15,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Vendor scoring matrix started yesterday.',
            ],
            [
                'title' => 'Organize cross-departmental workshop on digital process automation',
                'description' => 'Prepare presentation on paperless document approvals and electronic forms.',
                'officer' => 3,
                'offset' => 18,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::ToDo,
                'stale' => false,
                'note' => 'Workshop topics aligned this week.',
            ],
            [
                'title' => 'Publish annual sustainability benchmarks and energy consumption metrics',
                'description' => 'Calculate greenhouse gas reductions from municipal solar installation program.',
                'officer' => 3,
                'offset' => 28,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::InProgress,
                'stale' => false,
                'note' => 'Utility data collected across all facilities.',
            ],
            [
                'title' => 'Benchmark citizen satisfaction scores against regional service standards',
                'description' => 'Analyze survey responses and highlight key areas for turnaround time improvement.',
                'officer' => 4,
                'offset' => 20,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::ToDo,
                'stale' => false,
                'note' => 'Survey results received from analytics team.',
            ],
            [
                'title' => 'Draft modernization roadmap for legacy departmental archiving system',
                'description' => 'Identify integration hurdles with modern document management APIs.',
                'officer' => 1,
                'offset' => 35,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
                'stale' => false,
                'note' => 'Strategic roadmap delivered to IT committee.',
            ],
            [
                'title' => 'Establish cross-functional taskforce for civic technology pilot projects',
                'description' => 'Appoint departmental liaisons and schedule initial kick-off scoping session.',
                'officer' => 4,
                'offset' => 45,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
                'stale' => false,
                'note' => 'Charter approved and member appointments sent.',
            ],
        ];

        foreach ($definitions as $def) {
            $assignedOfficer = $officerMap[$def['officer']];

            $progressUpdatedAt = null;
            if ($def['status'] === TaskStatus::Done) {
                $progressUpdatedAt = now()->subDays(1);
            } elseif ($def['stale']) {
                // Stale: null progress update, or timestamp prior to start of current week
                $progressUpdatedAt = $def['note'] ? $startOfWeek->copy()->subDays(3) : null;
            } else {
                // Updated this week
                $progressUpdatedAt = $startOfWeek->copy()->addHours(6);
            }

            Task::create([
                'title' => $def['title'],
                'description' => $def['description'],
                'owner' => $assignedOfficer->name,
                'owner_id' => $assignedOfficer->id,
                'due_date' => $today->copy()->addDays($def['offset'])->toDateString(),
                'priority' => $def['priority'],
                'status' => $def['status'],
                'progress_note' => $def['note'],
                'progress_updated_at' => $progressUpdatedAt,
            ]);
        }
    }
}
