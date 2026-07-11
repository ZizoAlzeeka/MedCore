<?php
class DoctorSchedule extends Model
{
    protected $table = 'doctor_schedules';

    public function byDoctor($doctorId)
    {
        return Database::fetchAll(
            "SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY work_date, start_time",
            [$doctorId]
        );
    }

    public function byDoctorOnDate($doctorId, $date)
    {
        // Returns the first available shift on that date (legacy method)
        return Database::fetch(
            "SELECT * FROM doctor_schedules WHERE doctor_id = ? AND work_date = ? AND is_available = 1 ORDER BY start_time LIMIT 1",
            [$doctorId, $date]
        );
    }

    public function allByDoctorOnDate($doctorId, $date)
    {
        // Returns ALL shifts on that date (morning + evening, etc.)
        return Database::fetchAll(
            "SELECT * FROM doctor_schedules WHERE doctor_id = ? AND work_date = ? AND is_available = 1 ORDER BY start_time",
            [$doctorId, $date]
        );
    }

    public function availableSlots($doctorId, $date)
    {
        $schedules = $this->allByDoctorOnDate($doctorId, $date);
        if (!$schedules) return [];

        $allSlots = [];
        foreach ($schedules as $schedule) {
            $start = strtotime($schedule['start_time']);
            $end = strtotime($schedule['end_time']);
            $duration = max(15, (int) $schedule['slot_duration_min']);

            for ($t = $start; $t < $end; $t += $duration * 60) {
                $slotStart = date('H:i', $t);
                $slotEnd = date('H:i', $t + $duration * 60);

                // Skip if slot end exceeds schedule end
                if ($t + $duration * 60 > $end) break;

                // Check if booked
                $booked = Database::fetch(
                    "SELECT id FROM appointments
                     WHERE doctor_id = ? AND DATE(appt_date) = ? AND TIME(appt_date) = ?
                     AND status = 'booked'",
                    [$doctorId, $date, $slotStart]
                );
                $allSlots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'booked' => $booked ? true : false,
                    'shift' => $schedule['start_time'] . '-' . $schedule['end_time'],
                ];
            }
        }
        return $allSlots;
    }
}
