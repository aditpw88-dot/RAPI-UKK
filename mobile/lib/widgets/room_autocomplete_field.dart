import 'package:flutter/material.dart';
import '../models/room_model.dart';
import 'room_maintenance_dialog.dart';

class RoomAutocompleteField extends StatefulWidget {
  const RoomAutocompleteField({
    required this.rooms,
    required this.selectedRoomId,
    required this.onChanged,
    required this.labelText,
    required this.optionDetails,
    super.key,
  });

  final List<RoomModel> rooms;
  final int? selectedRoomId;
  final ValueChanged<int?> onChanged;
  final String labelText;
  final String Function(RoomModel room) optionDetails;

  @override
  State<RoomAutocompleteField> createState() => _RoomAutocompleteFieldState();
}

class _RoomAutocompleteFieldState extends State<RoomAutocompleteField> {
  TextEditingController? _fieldController;
  FocusNode? _fieldFocusNode;

  String _displayRoom(RoomModel room) =>
      '${room.roomName} (${widget.optionDetails(room)})';

  void _handleInactiveRoomTap(RoomModel room) {
    _fieldFocusNode?.unfocus();
    final prevSelected = widget.rooms.where(
      (r) => r.id == widget.selectedRoomId && r.isActive,
    );
    if (_fieldController != null) {
      _fieldController!.text =
          prevSelected.isEmpty ? '' : _displayRoom(prevSelected.first);
    }
    if (prevSelected.isEmpty) {
      widget.onChanged(null);
    }
    showRoomMaintenanceDialog(context, room);
  }

  @override
  Widget build(BuildContext context) {
    final selectedRoom = widget.rooms.where(
      (room) => room.id == widget.selectedRoomId && room.isActive,
    );

    return Autocomplete<RoomModel>(
      initialValue: TextEditingValue(
        text: selectedRoom.isEmpty ? '' : _displayRoom(selectedRoom.first),
      ),
      displayStringForOption: _displayRoom,
      optionsBuilder: (value) {
        final query = value.text.trim().toLowerCase();
        return widget.rooms.where(
          (room) =>
              query.isEmpty || _displayRoom(room).toLowerCase().contains(query),
        );
      },
      onSelected: (room) {
        if (!room.isActive) {
          _handleInactiveRoomTap(room);
          return;
        }
        widget.onChanged(room.id);
      },
      fieldViewBuilder: (context, controller, focusNode, onFieldSubmitted) {
        _fieldController = controller;
        _fieldFocusNode = focusNode;

        return TextField(
          controller: controller,
          focusNode: focusNode,
          decoration: InputDecoration(
            labelText: widget.labelText,
            hintText: 'Ketik nama ruangan untuk mencari',
            prefixIcon: const Icon(Icons.meeting_room_outlined),
            suffixIcon: const Icon(Icons.arrow_drop_down),
          ),
          onChanged: (text) {
            final currentRoom = widget.rooms.where(
              (room) => room.id == widget.selectedRoomId,
            );
            if (currentRoom.isEmpty || text != _displayRoom(currentRoom.first)) {
              widget.onChanged(null);
            }
          },
          onSubmitted: (_) => onFieldSubmitted(),
        );
      },
      optionsViewBuilder: (context, onSelected, options) {
        return Align(
          alignment: Alignment.topLeft,
          child: Material(
            elevation: 4,
            borderRadius: BorderRadius.circular(12),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 240, maxWidth: 420),
              child: ListView(
                padding: EdgeInsets.zero,
                shrinkWrap: true,
                children: options.map((room) {
                  final isInactive = !room.isActive;
                  return ListTile(
                    dense: true,
                    tileColor: isInactive ? const Color(0xFFFFFBEB) : null,
                    title: Row(
                      children: [
                        Expanded(
                          child: Text(
                            room.roomName,
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: isInactive
                                  ? const Color(0xFF92400E)
                                  : const Color(0xFF0F172A),
                            ),
                          ),
                        ),
                        if (isInactive)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEF3C7),
                              borderRadius: BorderRadius.circular(999),
                              border: Border.all(
                                color: const Color(0xFFFDE68A),
                              ),
                            ),
                            child: const Text(
                              'Dalam Perbaikan',
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF92400E),
                              ),
                            ),
                          ),
                      ],
                    ),
                    subtitle: Text(widget.optionDetails(room)),
                    onTap: () {
                      if (isInactive) {
                        _handleInactiveRoomTap(room);
                        return;
                      }
                      onSelected(room);
                    },
                  );
                }).toList(),
              ),
            ),
          ),
        );
      },
    );
  }
}
