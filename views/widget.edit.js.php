<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


use Modules\ProblemsBySvMnz\Widget;

?>

window.widget_problemsbysv_form = new class {

	init() {
		this._show_type = document.getElementById('show_type');
		if (this._show_type !== null) {
			this._show_type.addEventListener('change', () => this.updateForm());
			this.updateForm();
		}
	}

	updateForm() {
		const show_type = this._show_type.querySelector('input:checked').value;
		const show_type_totals = show_type == <?= Widget::SHOW_TOTALS ?>;
		const show_type_tags = show_type == <?= Widget::SHOW_TAGS ?>;

		document.getElementById('hide_empty_groups').disabled = show_type_totals || show_type_tags;

		for (const radio of document.querySelectorAll('#layout input')) {
			radio.disabled = !show_type_totals;
		}
	}
};