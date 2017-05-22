/*
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/** global: OC */
/** global: OCA */

(function () {


	/**
	 * @constructs Circles
	 */
	var Circles = function () {
		this.initialize();
	};

	Circles.prototype = {


		initialize: function () {

			var self = this;

			this.createCircle = function (type, name, callback) {

				var result = {status: -1};
				$.ajax({
					method: 'PUT',
					url: OC.generateUrl('/apps/circles/circles'),
					data: {
						type: type,
						name: name
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			// this.listCircles = function (type, callback) {
			// 	var result = {status: -1};
			// 	$.ajax({
			// 		method: 'GET',
			// 		url: OC.generateUrl(OC.linkTo('circles', 'circles')),
			// 		data: {
			// 			type: type
			// 		}
			// 	}).done(function (res) {
			// 		self.onCallback(callback, res);
			// 	}).fail(function () {
			// 		self.onCallback(callback, result);
			// 	});
			// };


			this.searchCircles = function (type, name, level, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'GET',
					url: OC.generateUrl('/apps/circles/circles'),
					data: {
						type: type,
						name: name,
						level: level
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.detailsCircle = function (circleId, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'GET',
					url: OC.generateUrl('/apps/circles/circles/' + circleId)
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.addMember = function (circleId, member, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'PUT',
					url: OC.generateUrl('/apps/circles/circles/' + circleId + '/members'),
					data: {
						name: member
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.removeMember = function (circleId, member, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'DELETE',
					url: OC.generateUrl('/apps/circles/circles/' + circleId + '/members'),
					data: {
						member: member
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.joinCircle = function (circleId, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'GET',
					url: OC.generateUrl('/apps/circles/circles/' + circleId + '/join'),
					data: {}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.leaveCircle = function (circleId, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'GET',
					url: OC.generateUrl('/apps/circles/circles/' + circleId + '/leave'),
					data: {}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.destroyCircle = function (circleId, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'DELETE',
					url: OC.generateUrl('/apps/circles/circles/' + circleId),
					data: {}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.shareToCircle = function (circleId, source, type, item, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'PUT',
					url: OC.generateUrl('/apps/circles/circles/' + circleId + '/share'),
					data: {
						source: source,
						type: type,
						item: item
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.linkCircle = function (circleId, remote, callback) {
				var result = {status: -1};
				$.ajax({
					method: 'PUT',
					url: OC.generateUrl('/apps/circles/circles/' + circleId + '/link'),
					data: {
						remote: remote
					}
				}).done(function (res) {
					self.onCallback(callback, res);
				}).fail(function () {
					self.onCallback(callback, result);
				});
			};


			this.onCallback = function (callback, result) {
				if (callback && (typeof callback === "function")) {
					callback(result);
				}
			};

		}

	};

	OCA.Circles = Circles;
	OCA.Circles.api = new Circles();

})();


