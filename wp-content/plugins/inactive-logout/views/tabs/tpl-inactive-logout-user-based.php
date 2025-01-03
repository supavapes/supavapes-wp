<div class="ina-settings-admin-wrap ina-settings-user-based">
    <table class="ina-form-tbl form-table">
        <tbody>
        <tr>
            <th scope="row"><label for="ina_enable_different_role_timeout">Multi-Role Timeout</label>
            </th>
            <td>
                <input name="ina_enable_different_role_timeout" type="checkbox" id="ina_enable_different_role_timeout" checked="" value="1">
                <p class="description">This will enable multi-user role timeout functionality.</p>
            </td>
        </tr>
        </tbody>
    </table>
    <table class="ina-form-tbl ina-multi-user-based-table wp-list-table widefat fixed striped pages">
        <thead>
        <tr>
            <th class="manage-column" style="width:8%;">User</th>
            <th class="manage-column" style="width:5%;">
                Timeout
                <div class="tooltip"><span class="dashicons dashicons-info"></span>
                    <span class="tooltiptext">Set different timeout duration for each user roles. Defined in minutes.</span>
                </div>
            </th>
            <th class="manage-column" style="width:20%;">
                Logout Redirect
                <div class="tooltip"><span class="dashicons dashicons-info"></span>
                    <span class="tooltiptext">Set different redirect page url for each user roles. This is affected when a user is logged out.</span>
                </div>
            </th>
            <th class="manage-column" style="width:6%;">
                Disable
                <div class="tooltip"><span class="dashicons dashicons-info"></span>
                    <span class="tooltiptext">Checking below will disable inactive logout functionality for selected user role.</span>
                </div>
            </th>
            <th class="manage-column" style="width:10%;">
                Multiple Logins
                <div class="tooltip"><span class="dashicons dashicons-info"></span>
                    <span class="tooltiptext tooltiptext-left">Checking below will prevent the selected user role from logging in at multiple locations.</span>
                </div>
            </th>
            <th class="manage-column" style="width:8%">
                Limit Logins
                <div class="tooltip"><span class="dashicons dashicons-info"></span>
                    <span class="tooltiptext tooltiptext-left">This will only work when "multiple logins" option is checked.</span>
                </div>
            </th>
            <th class="manage-column" style="width:20%;">Login Redirect</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Administrator</td>
            <td>
                <input type="number" min="1" max="1440" value="15">
            </td>
            <td>
                <select>
                    <option>Add url or Search by page name.</option>
                </select>
            </td>
            <td>
                <input type="checkbox">
            </td>
            <td>
                <input type="checkbox">
            </td>

            <td class="ina-addon-show-on-multiple-login-checked">
                <input type="number" min="1">
            </td>
            <td>
                <select>
                    <option>Add url or Search by page name.</option>
                </select>
            </td>

        </tr>
        <tr>
            <td>Subscriber</td>
            <td>
                <input type="number" min="1" max="1440" value="15" name="ina_individual_user_timeout[]">
            </td>
            <td>
                <select>
                    <option>Add url or Search by page name.</option>
                </select>
            </td>
            <td>
                <input type="checkbox">
            </td>
            <td>
                <input type="checkbox">
            </td>

            <td class="ina-addon-show-on-multiple-login-checked">
                <input type="number">
            </td>
            <td>
                <select>
                    <option>Add url or Search by page name.</option>
                </select>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="ina-floating-widget">
        <h2>User based setting is a PRO feature</h2>
        <p>Configure timeout, limit logins, redirects and more based on user.</p>
        <ul>
            <li><span class="dashicons dashicons-yes-alt"></span> Idle Timeout</li>
            <li><span class="dashicons dashicons-yes-alt"></span> Login Redirection</li>
            <li><span class="dashicons dashicons-yes-alt"></span> Logout Redirection</li>
            <li><span class="dashicons dashicons-yes-alt"></span> Disable Inactive Logout Functionality</li>
            <li><span class="dashicons dashicons-yes-alt"></span> Limit User logins</li>
        </ul>
        <div class="ina-floating-widget__link">
            <a href="https://www.inactive-logout.com/pricing" target="_blank">Unlock User Based Settings</a>
        </div>
    </div>
</div>