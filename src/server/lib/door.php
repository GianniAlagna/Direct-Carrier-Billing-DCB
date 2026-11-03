<?php
/**
 * QTPass - door decision table (docs/03-process-construct.md, section 7.3)
 *
 * Pure function: no database access, so it runs identically on the central
 * server and on the venue node in offline mode.
 */

define('QT_GREEN',  'GREEN');
define('QT_ORANGE', 'ORANGE');
define('QT_RED',    'RED');

class QT_Door
{
    /**
     * @param array $m  member: status, birth_date (Y-m-d), inside (0/1),
     *                  balance_rappen, has_entitlement (0/1)
     * @param array $e  event: date (Y-m-d), min_age, door_fee_rappen
     * @param bool  $codeOk  HOTP verified
     * @return array  colour, debit_rappen, message
     */
    function decide($m, $e, $codeOk)
    {
        if ($m['status'] == 'BLOCKED') {
            return $this->result(QT_RED, 0, 'Blocked - call night manager');
        }
        if (!$codeOk) {
            return $this->result(QT_RED, 0, 'Invalid code');
        }
        if ($this->age($m['birth_date'], $e['date']) < (int) $e['min_age']) {
            return $this->result(QT_RED, 0, 'Under age for this event - no entry');
        }
        if ($m['status'] == 'PENDING_ID') {
            return $this->result(QT_ORANGE, 0, 'First visit: check ID and take photo');
        }
        if ($m['status'] != 'ACTIVE') {
            return $this->result(QT_RED, 0, 'Account not active');
        }
        if ((int) $m['inside'] == 1) {
            return $this->result(QT_RED, 0, 'Already checked in - bouncer');
        }
        if ((int) $m['has_entitlement'] == 1) {
            return $this->result(QT_GREEN, 0, 'Presale / guest list');
        }
        if ((int) $m['balance_rappen'] >= (int) $e['door_fee_rappen']) {
            return $this->result(QT_GREEN, (int) $e['door_fee_rappen'],
                'CHF ' . $this->chf($e['door_fee_rappen']) . ' debited');
        }
        return $this->result(QT_ORANGE, 0, 'Balance too low - top up at counter or pay cash');
    }

    /** Full years between birth date and event date. */
    function age($birth, $onDate)
    {
        list($by, $bm, $bd) = explode('-', $birth);
        list($y, $mo, $d)   = explode('-', $onDate);
        $age = (int) $y - (int) $by;
        if ((int) $mo < (int) $bm || ((int) $mo == (int) $bm && (int) $d < (int) $bd)) {
            $age--;
        }
        return $age;
    }

    function chf($rappen)
    {
        return number_format($rappen / 100, 2, '.', "'");
    }

    function result($colour, $debit, $msg)
    {
        return array('colour' => $colour, 'debit_rappen' => $debit, 'message' => $msg);
    }
}
