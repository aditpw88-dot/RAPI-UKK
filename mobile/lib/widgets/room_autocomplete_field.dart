import 'package:flutter/material.dart';
import '../models/room_model.dart';

class RoomAutocompleteField extends StatelessWidget {
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

  String _displayRoom(RoomModel room) =>
      '${room.roomName} (${optionDetails(room)})';

  @override
  Widget build(BuildContext context) {
    final selectedRoom = rooms.where((room) => room.id == selectedRoomId);

    return Autocomplete<RoomModel>(
      initialValue: TextEditingValue(
        text: selectedRoom.isEmpty ? '' : _displayRoom(selectedRoom.first),
      ),
      displayStringForOption: _displayRoom,
      optionsBuilder: (value) {
        final query = value.text.trim().toLowerCase();
        return rooms.where(
          (room) =>
              query.isEmpty || _displayRoom(room).toLowerCase().contains(query),
        );
      },
      onSelected: (room) => onChanged(room.id),
      fieldViewBuilder: (context, controller, focusNode, onFieldSubmitted) {
        return TextField(
          controller: controller,
          focusNode: focusNode,
          decoration: InputDecoration(
            labelText: labelText,
            hintText: 'Ketik nama ruangan untuk mencari',
            prefixIcon: const Icon(Icons.meeting_room_outlined),
            suffixIcon: const Icon(Icons.arrow_drop_down),
          ),
          onChanged: (text) {
            final currentRoom = rooms.where(
              (room) => room.id == selectedRoomId,
            );
            if (currentRoom.isEmpty || text != _displayRoom(currentRoom.first)) {
              onChanged(null);
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
                children: options
                    .map(
                      (room) => ListTile(
                        dense: true,
                        title: Text(room.roomName),
                        subtitle: Text(optionDetails(room)),
                        onTap: () => onSelected(room),
                      ),
                    )
                    .toList(),
              ),
            ),
          ),
        );
      },
    );
  }
}
