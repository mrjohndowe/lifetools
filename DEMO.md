# LifeTools demo

Use a disposable install and fictional information.

1. Unzip the release under **htdocs** and open **http://localhost/lifetools/**. Select a mode, generate and save the key, create the demo administrator, and select the fictional seed. After applying, verify that **?setup=1** is locked.
2. Open Timeline. Confirm the four all-day fixtures and their order. Create a personal event, reschedule it, duplicate it, then move the copy to trash and restore it. Switch to the Calendar week and agenda views.
3. Create a weekly timed event. Set two skip dates and add a moved occurrence using **original YYYY-MM-DD = new YYYY-MM-DDTHH:MM**. View it in Calendar and add an in-app reminder.
4. Open Vault → Managed accounts → Example Planner. Attempt a reveal with the wrong app password, then with the correct one. Watch the value expire after 30 seconds. Open Compare banks to see last-four values and configured access fields.
5. Switch to dark mode from the header and back to system mode. Verify the preference survives refresh. Open Life admin → Private journal and Intimacy journal. Save an entry with optional answers left blank.
6. In dual mode, stop the disposable MySQL instance. Create several events and delete one. Verify the changes in Timeline and the degraded sync status. Start MySQL again, wait for the worker, then run Verify backup from Settings.
7. Create a backup in Settings. In a disposable installation, change a demo record, restore the named backup and sign back in. Verify the original record and vault reveal. For the mirror-only recovery, follow the procedure in the README with a dump made after syncing.
