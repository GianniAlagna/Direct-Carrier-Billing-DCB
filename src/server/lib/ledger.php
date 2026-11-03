<?php
/**
 * QTPass - wallet ledger. Append-only; balances are derived.
 */

require_once dirname(__FILE__) . '/db.php';

class QT_Ledger
{
    function balance($memberNo)
    {
        $row = qt_row('SELECT balance_rappen FROM member_balance WHERE member_no = ?', array($memberNo));
        return $row ? (int) $row['balance_rappen'] : 0;
    }

    /**
     * Book one entry and update the balance cache atomically.
     * Debits that would make the balance negative are refused.
     *
     * @return int|false new ledger id, false if refused or duplicate ref
     */
    function book($memberNo, $type, $amountRappen, $opts)
    {
        $db = qt_db();
        $db->beginTransaction();
        try {
            // Row lock on the member's balance (InnoDB)
            $row = qt_row('SELECT balance_rappen FROM member_balance WHERE member_no = ? FOR UPDATE',
                array($memberNo));
            $current = $row ? (int) $row['balance_rappen'] : 0;
            $new     = $current + (int) $amountRappen;

            if ($new < 0) {
                $db->rollBack();
                return false;
            }
            if ($amountRappen > 0 && $new > QT_MAX_BALANCE_RAPPEN && $type != 'REFUND') {
                $db->rollBack();
                return false;
            }

            $st = $db->prepare('INSERT INTO ledger_entry
                (member_no, type, amount_rappen, event_id, venue_id, terminal_id, staff_id, ref, reverses_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $st->execute(array(
                $memberNo, $type, (int) $amountRappen,
                $this->opt($opts, 'event_id'), $this->opt($opts, 'venue_id'),
                $this->opt($opts, 'terminal_id'), $this->opt($opts, 'staff_id'),
                $this->opt($opts, 'ref'), $this->opt($opts, 'reverses_id'),
            ));
            $id = (int) $db->lastInsertId();

            if ($row) {
                $st = $db->prepare('UPDATE member_balance SET balance_rappen = ?, updated_at = NOW() WHERE member_no = ?');
                $st->execute(array($new, $memberNo));
            } else {
                $st = $db->prepare('INSERT INTO member_balance (member_no, balance_rappen, updated_at) VALUES (?, ?, NOW())');
                $st->execute(array($memberNo, $new));
            }

            $db->commit();
            return $id;
        } catch (PDOException $e) {
            $db->rollBack();
            if ($e->getCode() == '23000') { // duplicate (type, ref): callback replayed
                qt_log('ledger duplicate ignored: ' . $type . ' ' . $this->opt($opts, 'ref'));
                return false;
            }
            throw $e;
        }
    }

    /** Reversal: books the negated amount, linked to the original row. */
    function reverse($ledgerId, $staffId, $reasonRef)
    {
        $orig = qt_row('SELECT * FROM ledger_entry WHERE id = ?', array($ledgerId));
        if (!$orig) {
            return false;
        }
        $map = array('ENTRY' => 'ENTRY_REVERSAL', 'BAR' => 'BAR_REVERSAL');
        if (!isset($map[$orig['type']])) {
            return false;
        }
        return $this->book($orig['member_no'], $map[$orig['type']], -(int) $orig['amount_rappen'], array(
            'event_id'    => $orig['event_id'],
            'venue_id'    => $orig['venue_id'],
            'staff_id'    => $staffId,
            'ref'         => 'REV-' . $ledgerId . '-' . $reasonRef,
            'reverses_id' => $ledgerId,
        ));
    }

    function opt($a, $k)
    {
        return isset($a[$k]) ? $a[$k] : null;
    }
}
